<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Visit;
use App\Models\WhatsappMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Cloud API service for ClinicOS.
 *
 * Supports: template messages, free-form text, documents, images,
 * plus purpose-built convenience methods for every automated trigger
 * (appointment confirmation, reminders, prescriptions, lab results,
 * follow-ups, payments, birthdays).
 *
 * Requires the following config keys in config/services.php → 'whatsapp':
 *   phone_number_id, token, api_version (default v19.0), verify_token, app_secret
 */
class WhatsAppService
{
    private string $phoneNumberId;
    private string $accessToken;
    private string $apiVersion;
    private string $baseUrl;

    public function __construct()
    {
        // Load from system_settings (super admin configured) first, then fall back to .env
        try {
            $this->phoneNumberId = \Illuminate\Support\Facades\DB::table('system_settings')
                ->where('key', 'whatsapp_phone_number_id')->value('value')
                ?: config('services.whatsapp.phone_number_id', '');

            $this->accessToken = \Illuminate\Support\Facades\DB::table('system_settings')
                ->where('key', 'whatsapp_access_token')->value('value')
                ?: config('services.whatsapp.token', '');
        } catch (\Throwable $e) {
            // DB might not be available (e.g. during migrations)
            $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
            $this->accessToken   = config('services.whatsapp.token', '');
        }

        $this->apiVersion = config('services.whatsapp.api_version', 'v19.0');
        $this->baseUrl    = "https://graph.facebook.com/{$this->apiVersion}";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Low-level send methods
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Send a pre-approved template message.
     *
     * @param  string  $phone         E.164 number (e.g. +919876543210)
     * @param  string  $templateName  Meta-approved template name
     * @param  array   $params        Ordered body parameter values
     * @param  string  $language      Template language code
     * @return array   Meta API response
     */
    public function sendTemplate(string $phone, string $templateName, array $params = [], string $language = 'en'): array
    {
        $phone = $this->formatPhone($phone);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'   => $phone,
            'type' => 'template',
            'template' => [
                'name'     => $templateName,
                'language' => ['code' => $language],
            ],
        ];

        if (!empty($params)) {
            $payload['template']['components'] = [
                [
                    'type'       => 'body',
                    'parameters' => array_map(
                        fn(string $v) => ['type' => 'text', 'text' => $v],
                        array_values($params)
                    ),
                ],
            ];
        }

        return $this->callApi("/{$this->phoneNumberId}/messages", $payload);
    }

    /**
     * Send a free-form text message (requires 24-hour customer window).
     */
    public function sendText(string $phone, string $message): array
    {
        $phone = $this->formatPhone($phone);

        return $this->callApi("/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'to'   => $phone,
            'type' => 'text',
            'text' => ['body' => $message],
        ]);
    }

    /**
     * Send a document (e.g. PDF prescription / invoice).
     */
    public function sendDocument(string $phone, string $documentUrl, string $filename, string $caption = ''): array
    {
        $phone = $this->formatPhone($phone);

        return $this->callApi("/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'to'   => $phone,
            'type' => 'document',
            'document' => [
                'link'     => $documentUrl,
                'filename' => $filename,
                'caption'  => $caption,
            ],
        ]);
    }

    /**
     * Send an image with optional caption.
     */
    public function sendImage(string $phone, string $imageUrl, string $caption = ''): array
    {
        $phone = $this->formatPhone($phone);

        return $this->callApi("/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'to'   => $phone,
            'type' => 'image',
            'image' => [
                'link'    => $imageUrl,
                'caption' => $caption,
            ],
        ]);
    }

    /**
     * Send a Razorpay / UPI payment link.
     */
    public function sendPaymentLink(
        string $phone,
        string $patientName,
        float  $amount,
        string $invoiceNumber,
        string $paymentUrl
    ): array {
        return $this->sendTemplate($phone, 'payment_link', [
            $patientName,
            $invoiceNumber,
            number_format($amount, 2),
            $paymentUrl,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Convenience / trigger methods
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Appointment confirmation — sent immediately on booking.
     */
    public function sendAppointmentConfirmation(Patient $patient, Appointment $appointment): array
    {
        $phone = $this->formatPhone($patient->phone);
        $date  = Carbon::parse($appointment->scheduled_at)->format('d M Y, h:i A');
        $doctor = $appointment->doctor->name ?? 'your doctor';

        $response = $this->sendTemplate($phone, 'appointment_confirmation', [
            $patient->name,
            $date,
            $doctor,
        ]);

        $this->logMessage(
            clinicId:     $appointment->clinic_id,
            patientId:    $patient->id,
            direction:    'outbound',
            triggerType:  'appointment_confirmation',
            templateName: 'appointment_confirmation',
            status:       'sent',
            response:     $response,
        );

        return $response;
    }

    /**
     * Send pre-visit questionnaire link (free-form text; may require an open WhatsApp session).
     */
    public function sendPreVisitQuestionnaireLink(Patient $patient, Appointment $appointment, string $url): array
    {
        Log::info('WhatsAppService: sendPreVisitQuestionnaireLink', [
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'url_length' => strlen($url),
        ]);

        $phone = $this->formatPhone($patient->phone);
        $message = "Hi {$patient->name}, please complete your pre-visit questionnaire before your appointment: {$url}";

        $response = $this->sendText($phone, $message);

        Log::info('WhatsAppService: sendPreVisitQuestionnaireLink result', [
            'appointment_id' => $appointment->id,
            'response_keys' => array_keys($response ?? []),
        ]);

        return $response;
    }

    /**
     * 24-hour appointment reminder.
     */
    public function sendAppointmentReminder24h(Patient $patient, Appointment $appointment): array
    {
        $phone = $this->formatPhone($patient->phone);
        $date  = Carbon::parse($appointment->scheduled_at)->format('d M Y, h:i A');
        $doctor = $appointment->doctor->name ?? 'your doctor';

        $response = $this->sendTemplate($phone, 'appointment_reminder_24h', [
            $patient->name,
            $date,
            $doctor,
        ]);

        $this->logMessage(
            clinicId:     $appointment->clinic_id,
            patientId:    $patient->id,
            direction:    'outbound',
            triggerType:  'appointment_reminder_24h',
            templateName: 'appointment_reminder_24h',
            status:       'sent',
            response:     $response,
        );

        return $response;
    }

    /**
     * 2-hour appointment reminder.
     */
    public function sendAppointmentReminder2h(Patient $patient, Appointment $appointment): array
    {
        $phone = $this->formatPhone($patient->phone);
        $time  = Carbon::parse($appointment->scheduled_at)->format('h:i A');
        $doctor = $appointment->doctor->name ?? 'your doctor';

        $response = $this->sendTemplate($phone, 'appointment_reminder_2h', [
            $patient->name,
            $time,
            $doctor,
        ]);

        $this->logMessage(
            clinicId:     $appointment->clinic_id,
            patientId:    $patient->id,
            direction:    'outbound',
            triggerType:  'appointment_reminder_2h',
            templateName: 'appointment_reminder_2h',
            status:       'sent',
            response:     $response,
        );

        return $response;
    }

    /**
     * Send prescription PDF via WhatsApp.
     */
    public function sendPrescription(Patient $patient, Prescription $prescription, string $pdfUrl): array
    {
        $phone = $this->formatPhone($patient->phone);
        $date  = $prescription->created_at?->format('d M Y') ?? now()->format('d M Y');

        $response = $this->sendDocument(
            $phone,
            $pdfUrl,
            "prescription_{$date}.pdf",
            "Your prescription from {$date}. Please take medicines as prescribed."
        );

        $this->logMessage(
            clinicId:     $prescription->clinic_id,
            patientId:    $patient->id,
            direction:    'outbound',
            triggerType:  'prescription',
            templateName: 'prescription_document',
            status:       'sent',
            response:     $response,
        );

        return $response;
    }

    /**
     * Send lab results notification.
     */
    public function sendLabResults(Patient $patient, LabOrder $labOrder): array
    {
        $phone = $this->formatPhone($patient->phone);

        $response = $this->sendTemplate($phone, 'lab_results_ready', [
            $patient->name,
            $labOrder->test_name ?? 'Lab Test',
            now()->format('d M Y'),
        ]);

        $this->logMessage(
            clinicId:     $labOrder->clinic_id,
            patientId:    $patient->id,
            direction:    'outbound',
            triggerType:  'lab_results',
            templateName: 'lab_results_ready',
            status:       'sent',
            response:     $response,
        );

        return $response;
    }

    /**
     * Follow-up reminder — sent on the scheduled follow-up date.
     */
    public function sendFollowUpReminder(Patient $patient, Visit $visit): array
    {
        $phone = $this->formatPhone($patient->phone);
        $visitDate = $visit->created_at?->format('d M Y') ?? '';

        $response = $this->sendTemplate($phone, 'followup_reminder', [
            $patient->name,
            $visitDate,
        ]);

        $this->logMessage(
            clinicId:     $visit->clinic_id,
            patientId:    $patient->id,
            direction:    'outbound',
            triggerType:  'followup_reminder',
            templateName: 'followup_reminder',
            status:       'sent',
            response:     $response,
        );

        return $response;
    }

    /**
     * Payment reminder for overdue invoices.
     */
    public function sendPaymentReminder(Patient $patient, Invoice $invoice, string $paymentUrl): array
    {
        $phone       = $this->formatPhone($patient->phone);
        $outstanding = round(($invoice->grand_total ?? 0) - ($invoice->amount_paid ?? 0), 2);

        $response = $this->sendTemplate($phone, 'payment_reminder', [
            $patient->name,
            $invoice->invoice_number ?? ('INV-' . $invoice->id),
            number_format($outstanding, 2),
            $paymentUrl,
        ]);

        $this->logMessage(
            clinicId:     $invoice->clinic_id,
            patientId:    $patient->id,
            direction:    'outbound',
            triggerType:  'payment_reminder',
            templateName: 'payment_reminder',
            status:       'sent',
            response:     $response,
        );

        return $response;
    }

    /**
     * Birthday greeting (sent automatically via cron).
     */
    public function sendBirthdayGreeting(Patient $patient): array
    {
        $phone = $this->formatPhone($patient->phone);

        $response = $this->sendTemplate($phone, 'birthday_greeting', [
            $patient->name,
        ]);

        $this->logMessage(
            clinicId:     $patient->clinic_id,
            patientId:    $patient->id,
            direction:    'outbound',
            triggerType:  'birthday_greeting',
            templateName: 'birthday_greeting',
            status:       'sent',
            response:     $response,
        );

        return $response;
    }

    /**
     * Alias used by web UI — free-form text within the 24h customer window.
     */
    public function sendMessage(string $phone, string $message): array
    {
        Log::info('WhatsAppService::sendMessage', [
            'phone_prefix' => substr($this->formatPhone($phone), 0, 5),
            'len' => strlen($message),
        ]);

        return $this->sendText($phone, $message);
    }

    /**
     * Send teleconsult / video visit link (plain text; Meta template optional in production).
     */
    public function sendTeleconsultInvite(Patient $patient, Appointment $appointment, string $meetingUrl): array
    {
        $phone = $this->formatPhone($patient->phone);
        $when = Carbon::parse($appointment->scheduled_at)->format('d M Y, h:i A');
        $clinicName = $appointment->clinic->name ?? 'Clinic';
        $msg = "Video consultation\n\nHello {$patient->name},\n\nYour teleconsult is scheduled for {$when}.\n\nJoin: {$meetingUrl}\n\n— {$clinicName}";

        Log::info('WhatsAppService::sendTeleconsultInvite', [
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
        ]);

        return $this->sendText($phone, $msg);
    }

    /**
     * Generic send — used by WhatsAppController for custom template sends.
     * $components is the Meta Cloud API `components` array.
     */
    public function send(string $phone, string $templateName, array $components = []): array
    {
        $phone = $this->formatPhone($phone);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'   => $phone,
            'type' => 'template',
            'template' => [
                'name'       => $templateName,
                'language'   => ['code' => 'en'],
                'components' => $components,
            ],
        ];

        return $this->callApi("/{$this->phoneNumberId}/messages", $payload);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Make a POST request to the Meta Cloud API.
     */
    private function callApi(string $endpoint, array $payload): array
    {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            Log::warning('WhatsApp credentials not configured — skipping API call', [
                'endpoint' => $endpoint,
            ]);

            return [
                'success' => false,
                'error'   => 'WhatsApp API credentials not configured',
            ];
        }

        try {
            $url = $this->baseUrl . $endpoint;

            Log::debug('WhatsApp API call', ['url' => $url, 'to' => $payload['to'] ?? null]);

            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->post($url, $payload);

            $body = $response->json();

            if ($response->successful()) {
                Log::info('WhatsApp message sent', [
                    'to'         => $payload['to'] ?? null,
                    'message_id' => $body['messages'][0]['id'] ?? null,
                ]);

                return array_merge($body, ['success' => true]);
            }

            Log::error('WhatsApp API error', [
                'status'   => $response->status(),
                'response' => $body,
            ]);

            return [
                'success' => false,
                'error'   => $body['error']['message'] ?? 'Unknown API error',
                'code'    => $body['error']['code'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp API exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Persist a WhatsApp message record for audit and analytics.
     */
    private function logMessage(
        int     $clinicId,
        int     $patientId,
        string  $direction,
        string  $triggerType,
        string  $templateName,
        string  $status,
        ?array  $response
    ): void {
        try {
            WhatsappMessage::create([
                'clinic_id'      => $clinicId,
                'patient_id'     => $patientId,
                'direction'      => $direction,
                'message_type'   => $triggerType,
                'template'       => $templateName,
                'status'         => $status,
                'wa_message_id'  => $response['messages'][0]['id'] ?? null,
                'payload'        => json_encode($response),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log WhatsApp message', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Normalise phone to E.164 with +91 for Indian mobiles.
     */
    private function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 10) {
            return '91' . $digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return $digits;
        }

        return ltrim($digits, '+');
    }
}
