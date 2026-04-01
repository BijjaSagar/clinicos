<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\WhatsappMessage;
use App\Models\WhatsappTemplate;
use App\Models\WhatsappReminder;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppWebController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
        Log::info('WhatsAppWebController: Initialized');
    }

    public function index(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('WhatsAppWebController@index', ['user' => auth()->id(), 'clinic_id' => $clinicId]);

        $messages = WhatsappMessage::with('patient')
            ->where('clinic_id', $clinicId)
            ->latest()
            ->paginate(30);

        $stats = [
            'sent_today' => WhatsappMessage::where('clinic_id', $clinicId)
                ->whereDate('created_at', today())
                ->where('direction', 'outbound')
                ->count(),
            'received_today' => WhatsappMessage::where('clinic_id', $clinicId)
                ->whereDate('created_at', today())
                ->where('direction', 'inbound')
                ->count(),
            'pending_replies' => WhatsappMessage::where('clinic_id', $clinicId)
                ->where('direction', 'inbound')
                ->where('status', 'unread')
                ->count(),
            'reminders_scheduled' => DB::table('whatsapp_reminders')
                ->where('clinic_id', $clinicId)
                ->where('is_active', true)
                ->count(),
            'templates_count' => DB::table('whatsapp_templates')
                ->where('clinic_id', $clinicId)
                ->where('is_active', true)
                ->count(),
        ];

        $templates = DB::table('whatsapp_templates')
            ->where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $reminders = DB::table('whatsapp_reminders')
            ->where('clinic_id', $clinicId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $upcomingAppointments = Appointment::with('patient')
            ->where('clinic_id', $clinicId)
            ->whereBetween('scheduled_at', [now(), now()->addDays(3)])
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get();

        Log::info('WhatsAppWebController@index: Loaded ' . $messages->count() . ' messages');

        return view('whatsapp.index', compact('messages', 'stats', 'templates', 'reminders', 'upcomingAppointments'));
    }

    public function send(Request $request)
    {
        Log::info('WhatsAppWebController@send', $request->all());

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'message' => 'required|string|max:4096',
            'template' => 'nullable|string',
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);

        try {
            $result = $this->whatsappService->sendMessage(
                $patient->phone,
                $validated['message']
            );

            WhatsappMessage::create([
                'clinic_id' => auth()->user()->clinic_id,
                'patient_id' => $patient->id,
                'direction' => 'outbound',
                'message_type' => 'text',
                'content' => $validated['message'],
                'status' => 'sent',
                'wa_message_id' => $result['message_id'] ?? null,
            ]);

            return back()->with('success', 'Message sent successfully');
        } catch (\Exception $e) {
            Log::error('WhatsApp send failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to send message: ' . $e->getMessage());
        }
    }

    public function broadcast(Request $request)
    {
        Log::info('WhatsAppWebController@broadcast', $request->all());

        $validated = $request->validate([
            'patient_ids' => 'required|array|min:1',
            'patient_ids.*' => 'exists:patients,id',
            'message' => 'required|string|max:4096',
        ]);

        $successCount = 0;
        $failCount = 0;

        foreach ($validated['patient_ids'] as $patientId) {
            $patient = Patient::find($patientId);
            if (!$patient || !$patient->phone) {
                $failCount++;
                continue;
            }

            try {
                $this->whatsappService->sendMessage($patient->phone, $validated['message']);
                $successCount++;
            } catch (\Exception $e) {
                Log::error('Broadcast message failed', [
                    'patient_id' => $patientId,
                    'error' => $e->getMessage()
                ]);
                $failCount++;
            }
        }

        return back()->with('success', "Broadcast complete: {$successCount} sent, {$failCount} failed");
    }

    /**
     * Send appointment reminder
     */
    public function sendAppointmentReminder(Request $request, Appointment $appointment): JsonResponse
    {
        Log::info('WhatsAppWebController: Sending appointment reminder', ['appointment_id' => $appointment->id]);

        $patient = $appointment->patient;
        if (!$patient || !$patient->phone) {
            return response()->json(['success' => false, 'error' => 'Patient phone not available'], 400);
        }

        $clinic = auth()->user()->clinic;
        $doctor = $appointment->doctor;
        $scheduledAt = $appointment->scheduled_at;

        $message = "🏥 *Appointment Reminder*\n\n";
        $message .= "Dear {$patient->name},\n\n";
        $message .= "This is a reminder for your upcoming appointment:\n\n";
        $message .= "📅 Date: " . $scheduledAt->format('l, d M Y') . "\n";
        $message .= "⏰ Time: " . $scheduledAt->format('h:i A') . "\n";
        if ($doctor) {
            $message .= "👨‍⚕️ Doctor: Dr. {$doctor->name}\n";
        }
        $message .= "\n📍 {$clinic->name}\n";
        if ($clinic->address) {
            $message .= $clinic->address . "\n";
        }
        $message .= "\nPlease arrive 10-15 minutes before your scheduled time.\n";
        $message .= "\nFor queries, contact: " . ($clinic->phone ?? 'N/A');

        $phone = $this->formatPhone($patient->phone);
        $waUrl = "https://api.whatsapp.com/send?phone={$phone}&text=" . urlencode($message);

        Log::info('WhatsAppWebController: Appointment reminder URL generated', ['appointment_id' => $appointment->id]);

        return response()->json([
            'success' => true,
            'whatsapp_url' => $waUrl,
            'message' => 'Reminder ready to send'
        ]);
    }

    /**
     * Send prescription via WhatsApp
     */
    public function sendPrescription(Request $request, Visit $visit): JsonResponse
    {
        Log::info('WhatsAppWebController: Sending prescription', ['visit_id' => $visit->id]);

        $visit->load(['patient', 'prescriptionItems', 'clinic']);
        $patient = $visit->patient;

        if (!$patient || !$patient->phone) {
            return response()->json(['success' => false, 'error' => 'Patient phone not available'], 400);
        }

        $clinic = $visit->clinic;
        $doctor = auth()->user();

        $message = "📋 *Digital Prescription*\n\n";
        $message .= "Patient: {$patient->name}\n";
        $message .= "Date: " . now()->format('d M Y') . "\n";
        $message .= "Doctor: Dr. {$doctor->name}\n\n";
        $message .= "*Medications:*\n";

        foreach ($visit->prescriptionItems as $index => $item) {
            $message .= "\n" . ($index + 1) . ". *{$item->drug_name}*\n";
            $message .= "   Dose: {$item->dosage}\n";
            $message .= "   Frequency: {$item->frequency_label}\n";
            $message .= "   Duration: {$item->duration}\n";
            if ($item->instructions) {
                $message .= "   📝 {$item->instructions}\n";
            }
        }

        if ($visit->diagnosis_text) {
            $message .= "\n*Diagnosis:* {$visit->diagnosis_text}\n";
        }

        if ($visit->followup_date) {
            $message .= "\n*Follow-up:* " . $visit->followup_date->format('d M Y') . "\n";
        } elseif ($visit->followup_in_days) {
            $message .= "\n*Follow-up:* After {$visit->followup_in_days} days\n";
        }

        $message .= "\n\n🏥 *{$clinic->name}*\n";
        $message .= $clinic->phone ?? '';

        $phone = $this->formatPhone($patient->phone);
        $waUrl = "https://api.whatsapp.com/send?phone={$phone}&text=" . urlencode($message);

        $visit->update(['prescription_sent_whatsapp' => true, 'prescription_sent_at' => now()]);

        Log::info('WhatsAppWebController: Prescription WhatsApp URL generated', ['visit_id' => $visit->id]);

        return response()->json([
            'success' => true,
            'whatsapp_url' => $waUrl,
        ]);
    }

    /**
     * Save WhatsApp template
     */
    public function saveTemplate(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('WhatsAppWebController: Saving template', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:appointment_reminder,prescription,follow_up,birthday,custom',
            'content' => 'required|string|max:4096',
            'variables' => 'nullable|array',
        ]);

        try {
            $templateId = DB::table('whatsapp_templates')->insertGetId([
                'clinic_id' => $clinicId,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'content' => $validated['content'],
                'variables' => json_encode($validated['variables'] ?? []),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('WhatsAppWebController: Template saved', ['template_id' => $templateId]);

            return response()->json([
                'success' => true,
                'message' => 'Template saved successfully',
                'template_id' => $templateId,
            ]);

        } catch (\Throwable $e) {
            Log::error('WhatsAppWebController: Error saving template', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get templates
     */
    public function getTemplates(): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('WhatsAppWebController: Getting templates', ['clinic_id' => $clinicId]);

        $templates = DB::table('whatsapp_templates')
            ->where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($templates);
    }

    /**
     * Delete template
     */
    public function deleteTemplate(int $templateId): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('WhatsAppWebController: Deleting template', ['template_id' => $templateId]);

        $deleted = DB::table('whatsapp_templates')
            ->where('id', $templateId)
            ->where('clinic_id', $clinicId)
            ->delete();

        return response()->json(['success' => $deleted > 0]);
    }

    /**
     * Schedule reminder
     */
    public function scheduleReminder(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('WhatsAppWebController: Scheduling reminder', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'type' => 'required|in:appointment_before_1d,appointment_before_1h,follow_up,birthday',
            'template_id' => 'nullable|exists:whatsapp_templates,id',
            'is_active' => 'boolean',
        ]);

        try {
            DB::table('whatsapp_reminders')->updateOrInsert(
                [
                    'clinic_id' => $clinicId,
                    'type' => $validated['type'],
                ],
                [
                    'template_id' => $validated['template_id'] ?? null,
                    'is_active' => $validated['is_active'] ?? true,
                    'updated_at' => now(),
                ]
            );

            Log::info('WhatsAppWebController: Reminder scheduled', ['type' => $validated['type']]);

            return response()->json(['success' => true, 'message' => 'Reminder settings saved']);

        } catch (\Throwable $e) {
            Log::error('WhatsAppWebController: Error scheduling reminder', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get upcoming reminders
     */
    public function getUpcomingReminders(): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('WhatsAppWebController: Getting upcoming reminders', ['clinic_id' => $clinicId]);

        $appointments = Appointment::with('patient')
            ->where('clinic_id', $clinicId)
            ->whereBetween('scheduled_at', [now(), now()->addDays(7)])
            ->orderBy('scheduled_at')
            ->get()
            ->map(function ($apt) {
                return [
                    'id' => $apt->id,
                    'patient_name' => $apt->patient?->name,
                    'patient_phone' => $apt->patient?->phone,
                    'scheduled_at' => $apt->scheduled_at->format('Y-m-d H:i'),
                    'formatted_date' => $apt->scheduled_at->format('d M Y h:i A'),
                    'days_until' => $apt->scheduled_at->diffInDays(now()),
                ];
            });

        return response()->json($appointments);
    }

    /**
     * Send bulk appointment reminders
     */
    public function sendBulkReminders(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('WhatsAppWebController: Sending bulk reminders', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'appointment_ids' => 'required|array|min:1',
            'appointment_ids.*' => 'exists:appointments,id',
        ]);

        $clinic = auth()->user()->clinic;
        $urls = [];

        foreach ($validated['appointment_ids'] as $appointmentId) {
            $appointment = Appointment::with(['patient', 'doctor'])->find($appointmentId);
            
            if (!$appointment || !$appointment->patient?->phone) {
                continue;
            }

            $patient = $appointment->patient;
            $doctor = $appointment->doctor;
            $scheduledAt = $appointment->scheduled_at;

            $message = "🏥 *Appointment Reminder*\n\n";
            $message .= "Dear {$patient->name},\n\n";
            $message .= "📅 " . $scheduledAt->format('l, d M Y') . "\n";
            $message .= "⏰ " . $scheduledAt->format('h:i A') . "\n";
            if ($doctor) {
                $message .= "👨‍⚕️ Dr. {$doctor->name}\n";
            }
            $message .= "\n🏥 {$clinic->name}";

            $phone = $this->formatPhone($patient->phone);
            $urls[] = [
                'appointment_id' => $appointment->id,
                'patient_name' => $patient->name,
                'whatsapp_url' => "https://api.whatsapp.com/send?phone={$phone}&text=" . urlencode($message),
            ];
        }

        Log::info('WhatsAppWebController: Bulk reminders generated', ['count' => count($urls)]);

        return response()->json([
            'success' => true,
            'reminders' => $urls,
            'count' => count($urls),
        ]);
    }

    /**
     * Send teleconsultation meeting link via WhatsApp (plain text).
     */
    public function sendTeleconsult(Request $request): RedirectResponse|JsonResponse
    {
        Log::info('WhatsAppWebController@sendTeleconsult', $request->all());

        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'meeting_url' => 'required|url|max:1000',
        ]);

        $clinicId = auth()->user()->clinic_id;
        $appointment = Appointment::with(['patient', 'clinic'])->findOrFail($validated['appointment_id']);
        abort_unless($appointment->clinic_id === $clinicId, 403);

        $patient = $appointment->patient;
        if (!$patient || !$patient->phone) {
            Log::warning('WhatsAppWebController@sendTeleconsult: missing patient phone', ['appointment_id' => $appointment->id]);

            return $request->wantsJson()
                ? response()->json(['success' => false, 'error' => 'Patient phone not available'], 400)
                : back()->with('error', 'Patient phone not available');
        }

        $response = $this->whatsappService->sendTeleconsultInvite($patient, $appointment, $validated['meeting_url']);

        $appointment->update([
            'teleconsult_meeting_url' => $validated['meeting_url'],
        ]);

        Log::info('WhatsAppWebController@sendTeleconsult: done', [
            'appointment_id' => $appointment->id,
            'api_success' => $response['success'] ?? null,
        ]);

        $ok = ($response['success'] ?? false) === true || isset($response['messages']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $ok,
                'whatsapp' => $response,
            ]);
        }

        return $ok
            ? back()->with('success', 'Teleconsult link sent via WhatsApp.')
            : back()->with('error', 'WhatsApp API did not confirm delivery: ' . ($response['error'] ?? 'unknown'));
    }

    /**
     * Format phone number for WhatsApp
     */
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }
        return $phone;
    }
}
