<?php

namespace App\Http\Controllers\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DoctorAvailability;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\WhatsApp\WhatsAppService;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedulingController extends Controller
{
    public function __construct(
        private readonly WhatsAppService $whatsApp,
    ) {}

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * GET /scheduling/appointments
     * List appointments with optional filters: date, doctor_id, status, patient_id.
     */
    public function index(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $query = Appointment::with(['patient', 'doctor', 'service'])
            ->where('clinic_id', $clinicId);

        if ($request->filled('date')) {
            $query->whereDate('start_at', Carbon::parse($request->date));
        }

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->integer('doctor_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->integer('patient_id'));
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('start_at', [
                Carbon::parse($request->from)->startOfDay(),
                Carbon::parse($request->to)->endOfDay(),
            ]);
        }

        $appointments = $query
            ->orderBy('start_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data'    => $appointments->items(),
            'message' => 'Appointments retrieved',
            'meta'    => [
                'total'        => $appointments->total(),
                'per_page'     => $appointments->perPage(),
                'current_page' => $appointments->currentPage(),
                'last_page'    => $appointments->lastPage(),
            ],
        ]);
    }

    /**
     * GET /scheduling/appointments/{id}
     * Single appointment with patient info and visit if exists.
     */
    public function show(int $id): JsonResponse
    {
        $appointment = Appointment::with(['patient', 'doctor', 'service', 'visit'])
            ->where('clinic_id', auth()->user()->clinic_id)
            ->findOrFail($id);

        return response()->json([
            'data'    => $appointment,
            'message' => 'Appointment retrieved',
            'meta'    => [],
        ]);
    }

    /**
     * POST /scheduling/appointments
     * Book a new appointment: validate slot availability, create record, send WhatsApp confirmation.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id'   => 'required|integer|exists:patients,id',
            'doctor_id'    => 'required|integer',
            'service_id'   => 'nullable|integer|exists:appointment_services,id',
            'start_at'     => 'required|date|after:now',
            'notes'        => 'nullable|string|max:1000',
            'room_id'      => 'nullable|integer|exists:clinic_rooms,id',
        ]);

        $clinicId = auth()->user()->clinic_id;
        $start    = Carbon::parse($validated['start_at']);

        // Determine duration from service or default to 15 min
        $durationMinutes = 15;
        if (! empty($validated['service_id'])) {
            $service         = DB::table('appointment_services')->find($validated['service_id']);
            $durationMinutes = $service?->duration_minutes ?? 15;
        }

        $end = $start->copy()->addMinutes($durationMinutes);

        if (! $this->checkSlotAvailable($validated['doctor_id'], $start, $end)) {
            return response()->json([
                'data'    => null,
                'message' => 'The selected time slot is not available.',
                'meta'    => [],
            ], 422);
        }

        $appointment = DB::transaction(function () use ($validated, $clinicId, $start, $end, $durationMinutes) {
            return Appointment::create([
                'clinic_id'        => $clinicId,
                'patient_id'       => $validated['patient_id'],
                'doctor_id'        => $validated['doctor_id'],
                'service_id'       => $validated['service_id'] ?? null,
                'room_id'          => $validated['room_id'] ?? null,
                'start_at'         => $start,
                'end_at'           => $end,
                'duration_minutes' => $durationMinutes,
                'status'           => 'booked',
                'notes'            => $validated['notes'] ?? null,
                'booked_by'        => auth()->id(),
            ]);
        });

        $appointment->load(['patient', 'doctor', 'service']);

        // Send WhatsApp confirmation (non-blocking — queue job)
        try {
            $this->whatsApp->sendAppointmentConfirmation($appointment);
        } catch (\Throwable) {
            // Notification failure must not roll back booking
        }

        return response()->json([
            'data'    => $appointment,
            'message' => 'Appointment booked successfully',
            'meta'    => [],
        ], 201);
    }

    /**
     * PUT /scheduling/appointments/{id}
     * Reschedule or change appointment status.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::where('clinic_id', auth()->user()->clinic_id)->findOrFail($id);

        $validated = $request->validate([
            'start_at'  => 'sometimes|date|after:now',
            'status'    => 'sometimes|in:booked,confirmed,checked_in,in_consultation,completed,cancelled',
            'notes'     => 'nullable|string|max:1000',
            'room_id'   => 'nullable|integer|exists:clinic_rooms,id',
            'doctor_id' => 'sometimes|integer',
        ]);

        DB::transaction(function () use ($appointment, $validated) {
            if (isset($validated['start_at'])) {
                $start = Carbon::parse($validated['start_at']);
                $end   = $start->copy()->addMinutes($appointment->duration_minutes);

                $doctorId = $validated['doctor_id'] ?? $appointment->doctor_id;

                if (! $this->checkSlotAvailable($doctorId, $start, $end, $appointment->id)) {
                    abort(422, 'The selected time slot is not available.');
                }

                $appointment->start_at  = $start;
                $appointment->end_at    = $end;
                $appointment->doctor_id = $doctorId;
            }

            $appointment->fill(array_filter($validated, fn($k) => ! in_array($k, ['start_at', 'doctor_id']), ARRAY_FILTER_USE_KEY));
            $appointment->save();
        });

        return response()->json([
            'data'    => $appointment->fresh(['patient', 'doctor', 'service']),
            'message' => 'Appointment updated',
            'meta'    => [],
        ]);
    }

    /**
     * POST /scheduling/appointments/{id}/cancel
     * Cancel appointment with reason, restore slot availability, send WhatsApp notification.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::where('clinic_id', auth()->user()->clinic_id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($appointment, $validated) {
            $appointment->update([
                'status'            => 'cancelled',
                'cancellation_reason' => $validated['reason'],
                'cancelled_at'      => now(),
                'cancelled_by'      => auth()->id(),
            ]);
        });

        try {
            $this->whatsApp->sendAppointmentCancellation($appointment, $validated['reason']);
        } catch (\Throwable) {
            // Notification failure must not affect cancellation
        }

        return response()->json([
            'data'    => $appointment,
            'message' => 'Appointment cancelled',
            'meta'    => [],
        ]);
    }

    // -------------------------------------------------------------------------
    // Slot Management
    // -------------------------------------------------------------------------

    /**
     * GET /scheduling/slots
     * Returns available time slots for a doctor on a given date.
     * Slot duration derived from service or doctor default (15/20/30 min).
     */
    public function getSlots(Request $request): JsonResponse
    {
        $request->validate([
            'doctor_id'  => 'required|integer',
            'date'       => 'required|date',
            'service_id' => 'nullable|integer|exists:appointment_services,id',
        ]);

        $clinicId  = auth()->user()->clinic_id;
        $doctorId  = $request->integer('doctor_id');
        $date      = Carbon::parse($request->date);
        $dayOfWeek = strtolower($date->format('l')); // monday, tuesday…

        // Fetch doctor's availability for this day
        $availability = DoctorAvailability::where('clinic_id', $clinicId)
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_blocked', false)
            ->first();

        if (! $availability) {
            return response()->json([
                'data'    => [],
                'message' => 'Doctor is not available on this day',
                'meta'    => ['date' => $date->toDateString(), 'doctor_id' => $doctorId],
            ]);
        }

        // Determine slot duration
        $slotMinutes = 15;
        if ($request->filled('service_id')) {
            $service     = DB::table('appointment_services')->find($request->integer('service_id'));
            $slotMinutes = $service?->duration_minutes ?? 15;
        } elseif ($availability->default_slot_minutes) {
            $slotMinutes = $availability->default_slot_minutes;
        }

        // Fetch already-booked slots for the day
        $booked = Appointment::where('clinic_id', $clinicId)
            ->where('doctor_id', $doctorId)
            ->whereDate('start_at', $date)
            ->whereNotIn('status', ['cancelled'])
            ->get(['start_at', 'end_at']);

        // Generate all slots between shift start and end
        $shiftStart = Carbon::parse($date->toDateString() . ' ' . $availability->start_time);
        $shiftEnd   = Carbon::parse($date->toDateString() . ' ' . $availability->end_time);

        // Remove lunch/break windows
        $breaks = $availability->breaks ?? [];

        $slots    = [];
        $current  = $shiftStart->copy();

        while ($current->copy()->addMinutes($slotMinutes)->lte($shiftEnd)) {
            $slotEnd = $current->copy()->addMinutes($slotMinutes);

            $inBreak = false;
            foreach ($breaks as $break) {
                $breakStart = Carbon::parse($date->toDateString() . ' ' . $break['start']);
                $breakEnd   = Carbon::parse($date->toDateString() . ' ' . $break['end']);
                if ($current->lt($breakEnd) && $slotEnd->gt($breakStart)) {
                    $inBreak = true;
                    break;
                }
            }

            $isBooked = false;
            if (! $inBreak) {
                foreach ($booked as $b) {
                    $bookedStart = Carbon::parse($b->start_at);
                    $bookedEnd   = Carbon::parse($b->end_at);
                    if ($current->lt($bookedEnd) && $slotEnd->gt($bookedStart)) {
                        $isBooked = true;
                        break;
                    }
                }
            }

            $slots[] = [
                'start'     => $current->toTimeString('minute'),
                'end'       => $slotEnd->toTimeString('minute'),
                'available' => ! $inBreak && ! $isBooked,
            ];

            $current->addMinutes($slotMinutes);
        }

        return response()->json([
            'data'    => $slots,
            'message' => 'Slots retrieved',
            'meta'    => [
                'date'         => $date->toDateString(),
                'doctor_id'    => $doctorId,
                'slot_minutes' => $slotMinutes,
                'total'        => count($slots),
                'available'    => collect($slots)->where('available', true)->count(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Queue Management
    // -------------------------------------------------------------------------

    /**
     * GET /scheduling/queue/today
     * Today's queue ordered: checked_in → confirmed → booked.
     * Includes queue position and estimated wait time per patient.
     */
    public function getTodayQueue(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        $doctorId = $request->integer('doctor_id', 0);

        $statusOrder = DB::raw("FIELD(status, 'checked_in', 'confirmed', 'booked', 'in_consultation')");

        $query = Appointment::with(['patient', 'doctor', 'service'])
            ->where('clinic_id', $clinicId)
            ->whereDate('start_at', today())
            ->whereIn('status', ['booked', 'confirmed', 'checked_in', 'in_consultation'])
            ->orderByRaw($statusOrder)
            ->orderBy('start_at');

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $queue = $query->get();

        $waitInfo = $this->calculateQueueWait($clinicId, now());

        $queueData = $queue->values()->map(function ($appt, $index) use ($waitInfo) {
            return array_merge(
                $this->buildCalendarEvent($appt),
                [
                    'queue_position'       => $index + 1,
                    'estimated_wait_min'   => $waitInfo['avg_minutes_per_patient'] * $index,
                ]
            );
        });

        return response()->json([
            'data'    => $queueData,
            'message' => 'Today\'s queue retrieved',
            'meta'    => [
                'date'              => today()->toDateString(),
                'total'             => $queue->count(),
                'avg_wait_minutes'  => $waitInfo['avg_minutes_per_patient'],
                'in_consultation'   => $queue->where('status', 'in_consultation')->count(),
                'checked_in'        => $queue->where('status', 'checked_in')->count(),
            ],
        ]);
    }

    /**
     * POST /scheduling/appointments/{id}/check-in
     * Mark patient as checked_in and update queue position.
     */
    public function checkIn(int $id): JsonResponse
    {
        $appointment = Appointment::where('clinic_id', auth()->user()->clinic_id)
            ->whereIn('status', ['booked', 'confirmed'])
            ->findOrFail($id);

        $appointment->update([
            'status'          => 'checked_in',
            'checked_in_at'   => now(),
        ]);

        return response()->json([
            'data'    => $appointment->fresh('patient'),
            'message' => 'Patient checked in',
            'meta'    => [],
        ]);
    }

    /**
     * POST /scheduling/appointments/{id}/start-consultation
     * Mark as in_consultation, create a draft visit record if one does not exist.
     */
    public function startConsultation(int $id): JsonResponse
    {
        $appointment = Appointment::with('patient')
            ->where('clinic_id', auth()->user()->clinic_id)
            ->where('status', 'checked_in')
            ->findOrFail($id);

        $visit = DB::transaction(function () use ($appointment) {
            $appointment->update([
                'status'             => 'in_consultation',
                'consultation_started_at' => now(),
            ]);

            // Create draft visit if not already linked
            if (! $appointment->visit_id) {
                $visit = Visit::firstOrCreate(
                    ['appointment_id' => $appointment->id, 'clinic_id' => $appointment->clinic_id],
                    [
                        'patient_id'   => $appointment->patient_id,
                        'doctor_id'    => $appointment->doctor_id,
                        'clinic_id'    => $appointment->clinic_id,
                        'specialty'    => $appointment->service?->specialty ?? 'general',
                        'status'       => 'draft',
                        'started_at'   => now(),
                    ]
                );

                $appointment->update(['visit_id' => $visit->id]);

                return $visit;
            }

            return $appointment->visit;
        });

        return response()->json([
            'data'    => ['appointment' => $appointment->fresh(), 'visit' => $visit],
            'message' => 'Consultation started',
            'meta'    => [],
        ]);
    }

    /**
     * POST /scheduling/appointments/{id}/complete
     * Mark appointment as completed and link to a visit_id.
     */
    public function completeVisit(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::where('clinic_id', auth()->user()->clinic_id)
            ->where('status', 'in_consultation')
            ->findOrFail($id);

        $validated = $request->validate([
            'visit_id' => 'nullable|integer|exists:visits,id',
        ]);

        $appointment->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'visit_id'     => $validated['visit_id'] ?? $appointment->visit_id,
        ]);

        return response()->json([
            'data'    => $appointment->fresh(),
            'message' => 'Appointment completed',
            'meta'    => [],
        ]);
    }

    // -------------------------------------------------------------------------
    // Doctor Availability
    // -------------------------------------------------------------------------

    /**
     * GET /scheduling/availability/{doctorId}
     * Returns the doctor's full weekly availability template.
     */
    public function getDoctorAvailability(int $doctorId): JsonResponse
    {
        $availability = DoctorAvailability::where('clinic_id', auth()->user()->clinic_id)
            ->where('doctor_id', $doctorId)
            ->orderByRaw("FIELD(day_of_week, 'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
            ->get();

        return response()->json([
            'data'    => $availability,
            'message' => 'Doctor availability retrieved',
            'meta'    => ['doctor_id' => $doctorId],
        ]);
    }

    /**
     * POST /scheduling/availability/{doctorId}
     * Upsert the doctor's weekly availability schedule.
     */
    public function setDoctorAvailability(Request $request, int $doctorId): JsonResponse
    {
        $validated = $request->validate([
            'slots'                       => 'required|array|min:1',
            'slots.*.day_of_week'         => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'slots.*.start_time'          => 'required|date_format:H:i',
            'slots.*.end_time'            => 'required|date_format:H:i|after:slots.*.start_time',
            'slots.*.default_slot_minutes' => 'nullable|integer|in:10,15,20,30,45,60',
            'slots.*.is_blocked'          => 'nullable|boolean',
            'slots.*.breaks'              => 'nullable|array',
            'slots.*.breaks.*.start'      => 'required_with:slots.*.breaks|date_format:H:i',
            'slots.*.breaks.*.end'        => 'required_with:slots.*.breaks|date_format:H:i',
        ]);

        $clinicId = auth()->user()->clinic_id;

        DB::transaction(function () use ($validated, $clinicId, $doctorId) {
            foreach ($validated['slots'] as $slot) {
                DoctorAvailability::updateOrCreate(
                    [
                        'clinic_id'  => $clinicId,
                        'doctor_id'  => $doctorId,
                        'day_of_week' => $slot['day_of_week'],
                    ],
                    [
                        'start_time'            => $slot['start_time'],
                        'end_time'              => $slot['end_time'],
                        'default_slot_minutes'  => $slot['default_slot_minutes'] ?? 15,
                        'is_blocked'            => $slot['is_blocked'] ?? false,
                        'breaks'                => $slot['breaks'] ?? [],
                    ]
                );
            }
        });

        return response()->json([
            'data'    => null,
            'message' => 'Doctor availability updated',
            'meta'    => ['doctor_id' => $doctorId, 'slots_updated' => count($validated['slots'])],
        ]);
    }

    /**
     * POST /scheduling/availability/{doctorId}/block
     * Block a time range for lunch, leave, meeting, etc.
     */
    public function blockSlot(Request $request, int $doctorId): JsonResponse
    {
        $validated = $request->validate([
            'start_at' => 'required|date',
            'end_at'   => 'required|date|after:start_at',
            'reason'   => 'required|string|max:255',
            'repeat'   => 'nullable|in:none,daily,weekly',
        ]);

        $clinicId = auth()->user()->clinic_id;
        $start    = Carbon::parse($validated['start_at']);
        $end      = Carbon::parse($validated['end_at']);

        // Check for existing appointments in this range
        $conflicting = Appointment::where('clinic_id', $clinicId)
            ->where('doctor_id', $doctorId)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->count();

        DB::transaction(function () use ($clinicId, $doctorId, $validated, $start) {
            // Store as a blocked availability record on the specific day
            DoctorAvailability::create([
                'clinic_id'    => $clinicId,
                'doctor_id'    => $doctorId,
                'day_of_week'  => strtolower($start->format('l')),
                'start_time'   => $start->format('H:i'),
                'end_time'     => Carbon::parse($validated['end_at'])->format('H:i'),
                'is_blocked'   => true,
                'block_reason' => $validated['reason'],
                'block_date'   => $start->toDateString(),
                'repeat'       => $validated['repeat'] ?? 'none',
            ]);
        });

        return response()->json([
            'data'    => null,
            'message' => 'Slot blocked',
            'meta'    => [
                'conflicting_appointments' => $conflicting,
                'warning' => $conflicting > 0
                    ? "{$conflicting} existing appointment(s) fall within this block."
                    : null,
            ],
        ], 201);
    }

    // -------------------------------------------------------------------------
    // Calendar View
    // -------------------------------------------------------------------------

    /**
     * GET /scheduling/calendar
     * Returns appointments grouped by day for week/month calendar rendering.
     */
    public function calendarView(Request $request): JsonResponse
    {
        $request->validate([
            'view'      => 'required|in:week,month',
            'date'      => 'required|date',
            'doctor_id' => 'nullable|integer',
        ]);

        $clinicId = auth()->user()->clinic_id;
        $pivot    = Carbon::parse($request->date);

        [$rangeStart, $rangeEnd] = match ($request->view) {
            'week'  => [$pivot->copy()->startOfWeek(), $pivot->copy()->endOfWeek()],
            'month' => [$pivot->copy()->startOfMonth(), $pivot->copy()->endOfMonth()],
        };

        $query = Appointment::with(['patient', 'doctor', 'service'])
            ->where('clinic_id', $clinicId)
            ->whereBetween('start_at', [$rangeStart, $rangeEnd])
            ->whereNotIn('status', ['cancelled']);

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->integer('doctor_id'));
        }

        $appointments = $query->orderBy('start_at')->get();

        // Group by date string
        $grouped = $appointments
            ->groupBy(fn($a) => Carbon::parse($a->start_at)->toDateString())
            ->map(fn($dayAppts) => $dayAppts->map(fn($a) => $this->buildCalendarEvent($a))->values());

        return response()->json([
            'data'    => $grouped,
            'message' => 'Calendar data retrieved',
            'meta'    => [
                'view'       => $request->view,
                'range_start' => $rangeStart->toDateString(),
                'range_end'   => $rangeEnd->toDateString(),
                'total'       => $appointments->count(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Statistics
    // -------------------------------------------------------------------------

    /**
     * GET /scheduling/stats
     * Scheduling stats: utilization rate, no-shows, avg wait time, busiest hours.
     */
    public function stats(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        $from     = Carbon::parse($request->input('from', now()->startOfMonth()));
        $to       = Carbon::parse($request->input('to', now()->endOfMonth()));

        $base = Appointment::where('clinic_id', $clinicId)
            ->whereBetween('start_at', [$from, $to]);

        $total       = (clone $base)->count();
        $completed   = (clone $base)->where('status', 'completed')->count();
        $cancelled   = (clone $base)->where('status', 'cancelled')->count();
        $noShows     = (clone $base)->where('status', 'no_show')->count();

        // Avg wait time: diff between checked_in_at and consultation_started_at
        $avgWait = (clone $base)
            ->whereNotNull('checked_in_at')
            ->whereNotNull('consultation_started_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, checked_in_at, consultation_started_at)) as avg_wait')
            ->value('avg_wait');

        // Busiest hours: group by hour of start_at
        $busiestHours = (clone $base)
            ->selectRaw('HOUR(start_at) as hour, COUNT(*) as count')
            ->groupByRaw('HOUR(start_at)')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'hour');

        // Utilization: completed / (total non-cancelled) * 100
        $nonCancelled  = $total - $cancelled;
        $utilization   = $nonCancelled > 0 ? round(($completed / $nonCancelled) * 100, 1) : 0;

        return response()->json([
            'data'    => [
                'total_appointments' => $total,
                'completed'          => $completed,
                'cancelled'          => $cancelled,
                'no_shows'           => $noShows,
                'utilization_rate'   => $utilization,
                'avg_wait_minutes'   => round($avgWait ?? 0, 1),
                'busiest_hours'      => $busiestHours,
            ],
            'message' => 'Stats retrieved',
            'meta'    => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Check if a doctor's slot is free between $start and $end.
     * Optionally exclude an appointment ID (for reschedules).
     */
    private function checkSlotAvailable(int $doctorId, Carbon $start, Carbon $end, ?int $excludeId = null): bool
    {
        $query = Appointment::where('doctor_id', $doctorId)
            ->whereNotIn('status', ['cancelled'])
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->doesntExist();
    }

    /**
     * Calculate queue wait statistics for the clinic right now.
     *
     * @return array{avg_minutes_per_patient: int, current_queue_length: int}
     */
    private function calculateQueueWait(int $clinicId, Carbon $now): array
    {
        // Use avg consultation duration from completed visits today, fallback to 15 min
        $avgDuration = Appointment::where('clinic_id', $clinicId)
            ->whereDate('start_at', $now->toDateString())
            ->where('status', 'completed')
            ->whereNotNull('consultation_started_at')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, consultation_started_at, completed_at)) as avg')
            ->value('avg');

        $avgMinutes = (int) max(round($avgDuration ?? 15), 5);

        $queueLength = Appointment::where('clinic_id', $clinicId)
            ->whereDate('start_at', $now->toDateString())
            ->whereIn('status', ['checked_in', 'in_consultation'])
            ->count();

        return [
            'avg_minutes_per_patient' => $avgMinutes,
            'current_queue_length'    => $queueLength,
        ];
    }

    /**
     * Format an Appointment model as a calendar event array for the frontend.
     */
    private function buildCalendarEvent(Appointment $appt): array
    {
        return [
            'id'             => $appt->id,
            'title'          => $appt->patient?->name ?? 'Unknown Patient',
            'start'          => Carbon::parse($appt->start_at)->toIso8601String(),
            'end'            => Carbon::parse($appt->end_at)->toIso8601String(),
            'status'         => $appt->status,
            'doctor_id'      => $appt->doctor_id,
            'doctor_name'    => $appt->doctor?->name ?? null,
            'patient_id'     => $appt->patient_id,
            'patient_name'   => $appt->patient?->name ?? null,
            'patient_phone'  => $appt->patient?->phone ?? null,
            'service'        => $appt->service?->service_name ?? null,
            'duration_min'   => $appt->duration_minutes,
            'room_id'        => $appt->room_id,
            'notes'          => $appt->notes,
            'visit_id'       => $appt->visit_id,
            'color'          => match ($appt->status) {
                'booked'          => '#93C5FD',
                'confirmed'       => '#6EE7B7',
                'checked_in'      => '#FCD34D',
                'in_consultation' => '#F97316',
                'completed'       => '#A3E635',
                'cancelled'       => '#F87171',
                default           => '#CBD5E1',
            },
        ];
    }
}
