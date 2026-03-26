<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Prescription;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl = 'https://graph.facebook.com/v18.0';
    protected string $accessToken;

    public function __construct()
    {
        $this->accessToken = config('services.whatsapp.access_token');
        Log::info('WhatsAppService initialized');
    }

    /**
     * Send a template message
     */
    public function sendTemplate(
        Clinic $clinic,
        Patient $patient,
        string $templateName,
        array $components = [],
        ?string $triggerType = null,
        ?int $relatedId = null
    ): ?WhatsappMessage {
        Log::info('Sending WhatsApp template', [
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'template' => $templateName,
            'trigger_type' => $triggerType,
        ]);

        if (!$clinic->whatsapp_phone_number_id) {
            Log::warning('WhatsApp not configured for clinic', ['clinic_id' => $clinic->id]);
            return null;
        }

        $phoneNumberId = $clinic->whatsapp_phone_number_id;
        $recipientPhone = $this->formatPhoneNumber($patient->phone);

        try {
            $response = Http::withToken($this->accessToken)
                ->post("{$this->apiUrl}/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipientPhone,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => ['code' => 'en'],
                        'components' => $components,
                    ],
                ]);

            Log::info('WhatsApp API response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                $messageId = $response->json('messages.0.id');

                $message = WhatsappMessage::create([
                    'clinic_id' => $clinic->id,
                    'patient_id' => $patient->id,
                    'direction' => WhatsappMessage::DIRECTION_OUTBOUND,
                    'wa_message_id' => $messageId,
                    'wa_phone_to' => $recipientPhone,
                    'template_name' => $templateName,
                    'message_type' => 'template',
                    'trigger_type' => $triggerType,
                    'related_id' => $relatedId,
                    'status' => WhatsappMessage::STATUS_SENT,
                    'sent_at' => now(),
                ]);

                Log::info('WhatsApp message recorded', ['message_id' => $message->id]);
                return $message;
            } else {
                Log::error('WhatsApp API error', [
                    'status' => $response->status(),
                    'error' => $response->json(),
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp send failed', [
                'error' => $e->getMessage(),
                'patient_id' => $patient->id,
            ]);
            return null;
        }
    }

    /**
     * Send text message
     */
    public function sendText(
        Clinic $clinic,
        Patient $patient,
        string $text,
        ?string $triggerType = null,
        ?int $relatedId = null
    ): ?WhatsappMessage {
        Log::info('Sending WhatsApp text', [
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'text_length' => strlen($text),
        ]);

        if (!$clinic->whatsapp_phone_number_id) {
            Log::warning('WhatsApp not configured for clinic', ['clinic_id' => $clinic->id]);
            return null;
        }

        $phoneNumberId = $clinic->whatsapp_phone_number_id;
        $recipientPhone = $this->formatPhoneNumber($patient->phone);

        try {
            $response = Http::withToken($this->accessToken)
                ->post("{$this->apiUrl}/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipientPhone,
                    'type' => 'text',
                    'text' => [
                        'body' => $text,
                    ],
                ]);

            Log::info('WhatsApp text API response', [
                'status' => $response->status(),
            ]);

            if ($response->successful()) {
                $messageId = $response->json('messages.0.id');

                $message = WhatsappMessage::create([
                    'clinic_id' => $clinic->id,
                    'patient_id' => $patient->id,
                    'direction' => WhatsappMessage::DIRECTION_OUTBOUND,
                    'wa_message_id' => $messageId,
                    'wa_phone_to' => $recipientPhone,
                    'message_type' => 'text',
                    'body' => $text,
                    'trigger_type' => $triggerType,
                    'related_id' => $relatedId,
                    'status' => WhatsappMessage::STATUS_SENT,
                    'sent_at' => now(),
                ]);

                Log::info('WhatsApp text message recorded', ['message_id' => $message->id]);
                return $message;
            } else {
                Log::error('WhatsApp text API error', [
                    'status' => $response->status(),
                    'error' => $response->json(),
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp text send failed', [
                'error' => $e->getMessage(),
                'patient_id' => $patient->id,
            ]);
            return null;
        }
    }

    /**
     * Send appointment confirmation
     */
    public function sendAppointmentConfirmation(Appointment $appointment): ?WhatsappMessage
    {
        Log::info('Sending appointment confirmation', ['appointment_id' => $appointment->id]);

        $clinic = $appointment->clinic;
        $patient = $appointment->patient;
        $doctor = $appointment->doctor;

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $patient->name],
                    ['type' => 'text', 'text' => $appointment->scheduled_at->format('d M Y')],
                    ['type' => 'text', 'text' => $appointment->scheduled_at->format('h:i A')],
                    ['type' => 'text', 'text' => $doctor->name],
                    ['type' => 'text', 'text' => $clinic->name],
                ],
            ],
        ];

        $message = $this->sendTemplate(
            $clinic,
            $patient,
            'appointment_confirmation',
            $components,
            WhatsappMessage::TRIGGER_APPOINTMENT_CONFIRMATION,
            $appointment->id
        );

        if ($message) {
            $appointment->update(['confirmation_sent_at' => now()]);
            Log::info('Appointment confirmation sent', ['appointment_id' => $appointment->id]);
        }

        return $message;
    }

    /**
     * Send 24-hour reminder
     */
    public function sendReminder24h(Appointment $appointment): ?WhatsappMessage
    {
        Log::info('Sending 24h reminder', ['appointment_id' => $appointment->id]);

        $clinic = $appointment->clinic;
        $patient = $appointment->patient;

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $patient->name],
                    ['type' => 'text', 'text' => $appointment->scheduled_at->format('d M Y, h:i A')],
                    ['type' => 'text', 'text' => $clinic->name],
                ],
            ],
        ];

        $message = $this->sendTemplate(
            $clinic,
            $patient,
            'appointment_reminder_24h',
            $components,
            WhatsappMessage::TRIGGER_REMINDER_24H,
            $appointment->id
        );

        if ($message) {
            $appointment->update(['reminder_24h_sent_at' => now()]);
            Log::info('24h reminder sent', ['appointment_id' => $appointment->id]);
        }

        return $message;
    }

    /**
     * Send 2-hour reminder
     */
    public function sendReminder2h(Appointment $appointment): ?WhatsappMessage
    {
        Log::info('Sending 2h reminder', ['appointment_id' => $appointment->id]);

        $clinic = $appointment->clinic;
        $patient = $appointment->patient;

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $patient->name],
                    ['type' => 'text', 'text' => $appointment->scheduled_at->format('h:i A')],
                    ['type' => 'text', 'text' => $clinic->name],
                ],
            ],
        ];

        $message = $this->sendTemplate(
            $clinic,
            $patient,
            'appointment_reminder_2h',
            $components,
            WhatsappMessage::TRIGGER_REMINDER_2H,
            $appointment->id
        );

        if ($message) {
            $appointment->update(['reminder_2h_sent_at' => now()]);
            Log::info('2h reminder sent', ['appointment_id' => $appointment->id]);
        }

        return $message;
    }

    /**
     * Send prescription
     */
    public function sendPrescription(Prescription $prescription): ?WhatsappMessage
    {
        Log::info('Sending prescription', ['prescription_id' => $prescription->id]);

        $clinic = $prescription->clinic;
        $patient = $prescription->patient;
        $doctor = $prescription->doctor;

        // Format prescription text
        $drugs = $prescription->drugs;
        $drugText = '';
        foreach ($drugs as $drug) {
            $drugText .= "• {$drug->drug_name} {$drug->dose} {$drug->frequency}\n";
        }

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $patient->name],
                    ['type' => 'text', 'text' => $doctor->name],
                    ['type' => 'text', 'text' => $prescription->created_at->format('d M Y')],
                ],
            ],
        ];

        // If PDF exists, send as document
        // For now, send template
        $message = $this->sendTemplate(
            $clinic,
            $patient,
            'prescription_sent',
            $components,
            WhatsappMessage::TRIGGER_PRESCRIPTION,
            $prescription->id
        );

        if ($message) {
            $prescription->update([
                'whatsapp_sent_at' => now(),
                'whatsapp_message_id' => $message->wa_message_id,
            ]);
            Log::info('Prescription sent', ['prescription_id' => $prescription->id]);
        }

        return $message;
    }

    /**
     * Send payment link
     */
    public function sendPaymentLink(Invoice $invoice, string $paymentLink): ?WhatsappMessage
    {
        Log::info('Sending payment link', ['invoice_id' => $invoice->id]);

        $clinic = $invoice->clinic;
        $patient = $invoice->patient;

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $patient->name],
                    ['type' => 'text', 'text' => $invoice->invoice_number],
                    ['type' => 'text', 'text' => '₹' . number_format($invoice->getBalanceDue(), 2)],
                    ['type' => 'text', 'text' => $paymentLink],
                ],
            ],
        ];

        $message = $this->sendTemplate(
            $clinic,
            $patient,
            'payment_link',
            $components,
            WhatsappMessage::TRIGGER_PAYMENT_LINK,
            $invoice->id
        );

        if ($message) {
            $invoice->update(['whatsapp_link_sent_at' => now()]);
            Log::info('Payment link sent', ['invoice_id' => $invoice->id]);
        }

        return $message;
    }

    /**
     * Send follow-up recall
     */
    public function sendRecall(Patient $patient, Clinic $clinic, string $message): ?WhatsappMessage
    {
        Log::info('Sending recall message', [
            'patient_id' => $patient->id,
            'clinic_id' => $clinic->id,
        ]);

        return $this->sendText(
            $clinic,
            $patient,
            $message,
            WhatsappMessage::TRIGGER_RECALL,
            $patient->id
        );
    }

    /**
     * Handle incoming webhook
     */
    public function handleWebhook(array $payload): void
    {
        Log::info('Processing WhatsApp webhook', ['payload' => $payload]);

        // Extract webhook data
        $entry = $payload['entry'][0] ?? null;
        if (!$entry) {
            Log::warning('No entry in webhook payload');
            return;
        }

        $changes = $entry['changes'][0] ?? null;
        if (!$changes) {
            Log::warning('No changes in webhook entry');
            return;
        }

        $value = $changes['value'] ?? null;
        if (!$value) {
            Log::warning('No value in webhook changes');
            return;
        }

        // Handle status updates
        if (isset($value['statuses'])) {
            foreach ($value['statuses'] as $status) {
                $this->handleStatusUpdate($status);
            }
        }

        // Handle incoming messages
        if (isset($value['messages'])) {
            foreach ($value['messages'] as $message) {
                $this->handleIncomingMessage($message, $value['metadata'] ?? []);
            }
        }
    }

    /**
     * Handle message status update
     */
    protected function handleStatusUpdate(array $status): void
    {
        $messageId = $status['id'] ?? null;
        $statusValue = $status['status'] ?? null;

        Log::info('WhatsApp status update', [
            'message_id' => $messageId,
            'status' => $statusValue,
        ]);

        if (!$messageId || !$statusValue) {
            return;
        }

        $message = WhatsappMessage::where('wa_message_id', $messageId)->first();
        if (!$message) {
            Log::warning('Message not found for status update', ['wa_message_id' => $messageId]);
            return;
        }

        switch ($statusValue) {
            case 'delivered':
                $message->markAsDelivered();
                break;
            case 'read':
                $message->markAsRead();
                break;
            case 'failed':
                $message->markAsFailed(
                    $status['errors'][0]['code'] ?? 'UNKNOWN',
                    $status['errors'][0]['message'] ?? 'Unknown error'
                );
                break;
        }
    }

    /**
     * Handle incoming message
     */
    protected function handleIncomingMessage(array $message, array $metadata): void
    {
        Log::info('Incoming WhatsApp message', ['message' => $message]);

        $from = $message['from'] ?? null;
        $text = $message['text']['body'] ?? null;
        $messageId = $message['id'] ?? null;

        if (!$from || !$messageId) {
            return;
        }

        // Find clinic by phone number ID
        $phoneNumberId = $metadata['phone_number_id'] ?? null;
        $clinic = Clinic::where('whatsapp_phone_number_id', $phoneNumberId)->first();

        if (!$clinic) {
            Log::warning('Clinic not found for phone number', ['phone_number_id' => $phoneNumberId]);
            return;
        }

        // Find patient by phone
        $patient = Patient::forClinic($clinic->id)
            ->where('phone', 'like', '%' . substr($from, -10))
            ->first();

        // Create message record
        WhatsappMessage::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient?->id,
            'direction' => WhatsappMessage::DIRECTION_INBOUND,
            'wa_message_id' => $messageId,
            'wa_phone_from' => $from,
            'message_type' => 'text',
            'body' => $text,
            'trigger_type' => WhatsappMessage::TRIGGER_INBOUND_REPLY,
            'status' => WhatsappMessage::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        Log::info('Incoming message recorded', [
            'wa_message_id' => $messageId,
            'patient_id' => $patient?->id,
        ]);
    }

    /**
     * Format phone number for WhatsApp API
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add India country code if not present
        if (strlen($phone) == 10) {
            $phone = '91' . $phone;
        }

        Log::debug('Formatted phone number', ['formatted' => $phone]);
        return $phone;
    }

    /**
     * Verify webhook token
     */
    public function verifyWebhook(string $mode, string $token, string $challenge): ?string
    {
        Log::info('Verifying WhatsApp webhook', ['mode' => $mode]);

        $verifyToken = config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Webhook verified successfully');
            return $challenge;
        }

        Log::warning('Webhook verification failed');
        return null;
    }
}
