<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMessage;
use App\Models\Patient;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function index(Request $request)
    {
        Log::info('WhatsAppWebController@index', ['user' => auth()->id()]);

        $messages = WhatsappMessage::with('patient')
            ->where('clinic_id', auth()->user()->clinic_id)
            ->latest()
            ->paginate(30);

        $stats = [
            'sent_today' => WhatsappMessage::where('clinic_id', auth()->user()->clinic_id)
                ->whereDate('created_at', today())
                ->where('direction', 'outbound')
                ->count(),
            'received_today' => WhatsappMessage::where('clinic_id', auth()->user()->clinic_id)
                ->whereDate('created_at', today())
                ->where('direction', 'inbound')
                ->count(),
            'pending_replies' => WhatsappMessage::where('clinic_id', auth()->user()->clinic_id)
                ->where('direction', 'inbound')
                ->where('status', 'unread')
                ->count(),
        ];

        return view('whatsapp.index', compact('messages', 'stats'));
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
}
