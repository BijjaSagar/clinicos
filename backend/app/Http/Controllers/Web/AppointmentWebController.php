<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\ClinicLocation;
use App\Models\Patient;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentWebController extends Controller
{
    private ?WhatsAppService $whatsAppService;

    public function __construct(?WhatsAppService $whatsAppService = null)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function index(Request $request): View
    {
        Log::info('AppointmentWebController@index');
        
        $clinicId = auth()->user()->clinic_id;

        // Get today's appointments by default
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : today();

        $appointments = Appointment::with(['patient'])
            ->where('clinic_id', $clinicId)
            ->whereDate('scheduled_at', $date)
            ->orderBy('scheduled_at')
            ->get();

        $clinic = auth()->user()->clinic;
        $settings = $clinic->settings ?? [];
        $slotMins = (int) ($settings['slot_duration_mins'] ?? 15);
        $timeSlots = $this->buildScheduleTimeSlotsForDate($date, $settings);

        $waitEstimates = [];
        foreach ($appointments as $apt) {
            if (in_array($apt->status, ['cancelled', 'no_show'], true)) {
                $waitEstimates[$apt->id] = ['ahead' => 0, 'minutes' => 0];
                continue;
            }
            $ahead = $appointments->filter(function ($a) use ($apt) {
                if ((int) $a->doctor_id !== (int) $apt->doctor_id) {
                    return false;
                }
                if (in_array($a->status, ['cancelled', 'no_show'], true)) {
                    return false;
                }

                return Carbon::parse($a->scheduled_at)->lt(Carbon::parse($apt->scheduled_at));
            })->count();
            $waitEstimates[$apt->id] = [
                'ahead' => $ahead,
                'minutes' => $ahead * $slotMins,
            ];
        }

        Log::info('Appointments loaded', [
            'count' => $appointments->count(),
            'date' => $date->toDateString(),
            'wait_estimates' => count($waitEstimates),
            'slot_mins' => $slotMins,
            'time_slots' => count($timeSlots),
        ]);

        return view('appointments.index', compact('appointments', 'waitEstimates', 'timeSlots'));
    }

    /**
     * Match public booking slot steps so web bookings appear in the schedule grid.
     *
     * @param  array<string, mixed>  $settings
     * @return list<string> H:i strings
     */
    private function buildScheduleTimeSlotsForDate(Carbon $date, array $settings): array
    {
        $slotMins = max(5, min(120, (int) ($settings['slot_duration_mins'] ?? 15)));
        // Defaults must match PublicBookingController (patient /book times)
        $start = $settings['clinic_start_time'] ?? '09:00';
        $end = $settings['clinic_end_time'] ?? '20:00';
        $day = $date->toDateString();

        $current = Carbon::parse($day.' '.$start);
        $endAt = Carbon::parse($day.' '.$end);
        $slots = [];

        while ($current->lt($endAt)) {
            $slots[] = $current->format('H:i');
            $current->addMinutes($slotMins);
        }

        Log::debug('AppointmentWebController: buildScheduleTimeSlotsForDate', [
            'day' => $day,
            'slot_mins' => $slotMins,
            'count' => count($slots),
        ]);

        return $slots;
    }

    public function create(): View
    {
        $clinicId = auth()->user()->clinic_id;

        $patients = Patient::where('clinic_id', $clinicId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $doctors = User::where('clinic_id', $clinicId)
            ->whereIn('role', ['doctor', 'admin'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'specialty']);

        $services = AppointmentService::where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'duration_mins', 'advance_amount']);

        $locations = ClinicLocation::where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'is_primary']);

        Log::info('AppointmentWebController@create', ['locations_count' => $locations->count()]);

        return view('appointments.create', compact('patients', 'doctors', 'services', 'locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        Log::info('AppointmentWebController@store', ['data' => $request->all()]);
        
        $clinicId = auth()->user()->clinic_id;

        $validated = $request->validate([
            'patient_id'       => ['required', 'integer', 'exists:patients,id'],
            'doctor_id'        => ['required', 'integer', 'exists:users,id'],
            'scheduled_date'   => ['required', 'date'],
            'scheduled_time'   => ['required', 'date_format:H:i'],
            'duration_mins'    => ['nullable', 'integer', 'min:5', 'max:480'],
            'appointment_type' => ['nullable', 'string', 'in:new,followup,procedure,teleconsultation'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'location_id'      => ['nullable', 'integer', Rule::exists('clinic_locations', 'id')->where('clinic_id', $clinicId)],
            'teleconsult_meeting_url' => ['nullable', 'url', 'max:1000'],
        ]);

        try {
            
            // Combine date and time
            $scheduledAt = Carbon::parse($validated['scheduled_date'] . ' ' . $validated['scheduled_time']);
            
            Log::info('Creating appointment', ['scheduled_at' => $scheduledAt]);

            // Get doctor's specialty or clinic's specialty
            $doctor = User::find($validated['doctor_id']);
            $clinic = auth()->user()->clinic;
            $specialty = $doctor->specialty ?? ($clinic->specialties[0] ?? 'general');

            $preVisitToken = Str::random(48);

            $appointment = Appointment::create([
                'clinic_id' => $clinicId,
                'patient_id' => $validated['patient_id'],
                'doctor_id' => $validated['doctor_id'],
                'scheduled_at' => $scheduledAt,
                'duration_mins' => $validated['duration_mins'] ?? 30,
                'appointment_type' => $validated['appointment_type'] ?? 'new',
                'specialty' => $specialty,
                'notes' => $validated['notes'] ?? null,
                'location_id' => $validated['location_id'] ?? null,
                'teleconsult_meeting_url' => $validated['teleconsult_meeting_url'] ?? null,
                'status' => 'confirmed',
                'booking_source' => 'clinic_staff', // Valid: clinic_staff, online_booking, whatsapp, phone, walk_in
                'pre_visit_token' => $preVisitToken,
            ]);

            Log::info('Appointment created with location/teleconsult', [
                'appointment_id' => $appointment->id,
                'location_id' => $appointment->location_id,
                'has_teleconsult_url' => (bool) $appointment->teleconsult_meeting_url,
                'has_pre_visit_token' => true,
            ]);

            if ($this->whatsAppService) {
                $patient = Patient::find($validated['patient_id']);
                $clinic = auth()->user()->clinic;
                $appointment->load('doctor');

                if ($patient && $clinic) {
                    try {
                        $this->whatsAppService->sendAppointmentConfirmation($patient, $appointment);
                        Log::info('WhatsApp appointment_confirmation sent', ['appointment_id' => $appointment->id]);
                    } catch (\Throwable $e) {
                        Log::warning('WhatsApp confirmation failed', [
                            'appointment_id' => $appointment->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    try {
                        $preVisitUrl = url('/book/' . $clinic->slug . '/pre-visit/' . $preVisitToken);
                        $this->whatsAppService->sendPreVisitQuestionnaireLink($patient, $appointment, $preVisitUrl);
                        Log::info('WhatsApp pre-visit link sent', [
                            'appointment_id' => $appointment->id,
                            'url_len' => strlen($preVisitUrl),
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('WhatsApp pre-visit link failed', [
                            'appointment_id' => $appointment->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('Appointment created via web', ['appointment_id' => $appointment->id]);

            return redirect()
                ->route('schedule')
                ->with('success', 'Appointment booked successfully.');
        } catch (\Throwable $e) {
            Log::error('Appointment store error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return back()
                ->withInput()
                ->with('error', 'Could not book appointment: ' . $e->getMessage());
        }
    }

    public function show(Appointment $appointment): View
    {
        abort_unless(auth()->user()->clinic_id === $appointment->clinic_id, 403);

        $appointment->load(['patient', 'doctor', 'service', 'visit', 'clinic']);

        Log::info('AppointmentWebController@show loaded', [
            'appointment_id' => $appointment->id,
            'has_pre_visit_token' => !empty($appointment->pre_visit_token),
        ]);

        return view('appointments.show', compact('appointment'));
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless(auth()->user()->clinic_id === $appointment->clinic_id, 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:confirmed,checked_in,in_consultation,completed,cancelled,no_show'],
        ]);

        $allowedTransitions = [
            'confirmed'       => ['checked_in', 'cancelled', 'no_show'],
            'checked_in'      => ['in_consultation', 'cancelled'],
            'in_consultation' => ['completed'],
            'completed'       => [],
            'cancelled'       => [],
            'no_show'         => [],
        ];

        $currentStatus   = $appointment->status;
        $newStatus       = $validated['status'];
        $validNextStates = $allowedTransitions[$currentStatus] ?? [];

        if (!in_array($newStatus, $validNextStates)) {
            return back()->with('error', "Cannot transition from '{$currentStatus}' to '{$newStatus}'.");
        }

        try {
            $appointment->update(['status' => $newStatus]);

            Log::info('Appointment status updated via web', [
                'appointment_id' => $appointment->id,
                'from'           => $currentStatus,
                'to'             => $newStatus,
            ]);

            return back()->with('success', 'Appointment status updated.');
        } catch (\Throwable $e) {
            Log::error('Appointment status update error', ['error' => $e->getMessage()]);

            return back()->with('error', 'Could not update status. Please try again.');
        }
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        abort_unless(auth()->user()->clinic_id === $appointment->clinic_id, 403);

        try {
            $patient = $appointment->patient()->first();
            $clinic  = auth()->user()->clinic()->first();

            $appointment->update([
                'status'           => 'cancelled',
                'cancelled_reason' => 'Cancelled by clinic via web portal',
            ]);
            $appointment->delete();

            // Send WhatsApp cancellation
            if ($patient && $clinic) {
                try {
                    $this->whatsAppService->sendTemplate(
                        $clinic,
                        $patient,
                        'appointment_cancelled',
                        [
                            [
                                'type'       => 'body',
                                'parameters' => [
                                    ['type' => 'text', 'text' => $patient->name],
                                    ['type' => 'text', 'text' => Carbon::parse($appointment->scheduled_at)->format('d M Y, h:i A')],
                                ],
                            ],
                        ],
                        'appointment_cancelled',
                        $appointment->id
                    );
                } catch (\Throwable $e) {
                    Log::warning('WhatsApp cancellation failed', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);
                }
            }

            Log::info('Appointment cancelled and deleted via web', ['appointment_id' => $appointment->id]);

            return redirect()
                ->route('appointments.index')
                ->with('success', 'Appointment cancelled and patient notified via WhatsApp.');
        } catch (\Throwable $e) {
            Log::error('Appointment destroy error', ['error' => $e->getMessage()]);

            return back()->with('error', 'Could not cancel appointment. Please try again.');
        }
    }
}
