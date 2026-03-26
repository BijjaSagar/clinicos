<?php

namespace App\Http\Controllers\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DoctorAvailability;
use App\Models\AppointmentService;
use App\Models\Patient;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * List appointments
     */
    public function index(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching appointments', [
            'clinic_id' => $clinicId,
            'date' => $request->date,
            'doctor_id' => $request->doctor_id
        ]);

        $query = Appointment::forClinic($clinicId)
            ->with(['patient', 'doctor', 'service', 'room']);

        if ($request->date) {
            $query->forDate($request->date);
        }

        if ($request->doctor_id) {
            $query->forDoctor($request->doctor_id);
        }

        if ($request->status) {
            $query->byStatus($request->status);
        }

        $appointments = $query->orderBy('scheduled_at', 'asc')->get();

        Log::info('Appointments retrieved', ['count' => $appointments->count()]);

        return response()->json([
            'appointments' => $appointments,
        ]);
    }

    /**
     * Create new appointment
     */
    public function store(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Creating appointment', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'service_id' => 'nullable|exists:appointment_services,id',
            'room_id' => 'nullable|exists:clinic_rooms,id',
            'equipment_id' => 'nullable|exists:clinic_equipment,id',
            'scheduled_at' => 'required|date|after:now',
            'duration_mins' => 'nullable|integer|min:5|max:240',
            'appointment_type' => 'nullable|in:new,followup,procedure,teleconsultation',
            'specialty' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        // Get duration from service if not provided
        $duration = $validated['duration_mins'] ?? 15;
        if ($validated['service_id']) {
            $service = AppointmentService::find($validated['service_id']);
            $duration = $service->duration_mins ?? $duration;
        }

        // Generate token number for today
        $todayCount = Appointment::forClinic($clinicId)
            ->whereDate('scheduled_at', Carbon::parse($validated['scheduled_at'])->toDateString())
            ->count();

        $appointment = Appointment::create([
            'clinic_id' => $clinicId,
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'service_id' => $validated['service_id'] ?? null,
            'room_id' => $validated['room_id'] ?? null,
            'equipment_id' => $validated['equipment_id'] ?? null,
            'scheduled_at' => $validated['scheduled_at'],
            'duration_mins' => $duration,
            'appointment_type' => $validated['appointment_type'] ?? 'new',
            'specialty' => $validated['specialty'],
            'status' => Appointment::STATUS_BOOKED,
            'token_number' => $todayCount + 1,
            'booking_source' => 'clinic_staff',
            'notes' => $validated['notes'] ?? null,
        ]);

        Log::info('Appointment created', [
            'appointment_id' => $appointment->id,
            'token' => $appointment->token_number
        ]);

        return response()->json([
            'message' => 'Appointment created successfully',
            'appointment' => $appointment->load(['patient', 'doctor', 'service']),
        ], 201);
    }

    /**
     * Show single appointment
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching appointment', ['appointment_id' => $id]);

        $appointment = Appointment::forClinic($clinicId)
            ->with(['patient', 'doctor', 'service', 'room', 'equipment', 'visit'])
            ->findOrFail($id);

        return response()->json([
            'appointment' => $appointment,
        ]);
    }

    /**
     * Update appointment
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Updating appointment', ['appointment_id' => $id]);

        $appointment = Appointment::forClinic($clinicId)->findOrFail($id);

        $validated = $request->validate([
            'doctor_id' => 'sometimes|exists:users,id',
            'service_id' => 'nullable|exists:appointment_services,id',
            'room_id' => 'nullable|exists:clinic_rooms,id',
            'scheduled_at' => 'sometimes|date',
            'duration_mins' => 'sometimes|integer|min:5|max:240',
            'notes' => 'nullable|string',
        ]);

        $appointment->update($validated);

        Log::info('Appointment updated', ['appointment_id' => $id]);

        return response()->json([
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment->fresh()->load(['patient', 'doctor', 'service']),
        ]);
    }

    /**
     * Cancel appointment
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Cancelling appointment', ['appointment_id' => $id]);

        $appointment = Appointment::forClinic($clinicId)->findOrFail($id);

        if (!$appointment->canCancel()) {
            Log::warning('Cannot cancel appointment', ['appointment_id' => $id, 'status' => $appointment->status]);
            return response()->json(['message' => 'Cannot cancel this appointment'], 400);
        }

        $appointment->update([
            'status' => Appointment::STATUS_CANCELLED,
            'cancelled_reason' => $request->reason ?? null,
        ]);

        Log::info('Appointment cancelled', ['appointment_id' => $id]);

        return response()->json([
            'message' => 'Appointment cancelled successfully',
        ]);
    }

    /**
     * Check in patient
     */
    public function checkIn(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Checking in patient', ['appointment_id' => $id]);

        $appointment = Appointment::forClinic($clinicId)->findOrFail($id);

        if (!$appointment->canCheckIn()) {
            Log::warning('Cannot check in', ['appointment_id' => $id, 'status' => $appointment->status]);
            return response()->json(['message' => 'Cannot check in for this appointment'], 400);
        }

        $appointment->markAsCheckedIn();

        Log::info('Patient checked in', ['appointment_id' => $id]);

        return response()->json([
            'message' => 'Patient checked in successfully',
            'appointment' => $appointment->fresh(),
        ]);
    }

    /**
     * Complete appointment
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Completing appointment', ['appointment_id' => $id]);

        $appointment = Appointment::forClinic($clinicId)->findOrFail($id);

        $appointment->markAsCompleted();

        // Increment patient visit count
        $appointment->patient->incrementVisitCount();

        Log::info('Appointment completed', ['appointment_id' => $id]);

        return response()->json([
            'message' => 'Appointment completed successfully',
            'appointment' => $appointment->fresh(),
        ]);
    }

    /**
     * Get available slots
     */
    public function availableSlots(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching available slots', [
            'clinic_id' => $clinicId,
            'doctor_id' => $request->doctor_id,
            'date' => $request->date
        ]);

        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
            'service_id' => 'nullable|exists:appointment_services,id',
        ]);

        $date = Carbon::parse($validated['date']);
        $dayOfWeek = $date->dayOfWeek;

        // Get doctor availability for this day
        $availability = DoctorAvailability::forDoctor($validated['doctor_id'])
            ->forDay($dayOfWeek)
            ->active()
            ->effectiveOn($date)
            ->first();

        if (!$availability) {
            Log::info('No availability found', ['doctor_id' => $validated['doctor_id'], 'day' => $dayOfWeek]);
            return response()->json(['slots' => []]);
        }

        // Get existing appointments for the day
        $existingAppointments = Appointment::forClinic($clinicId)
            ->forDoctor($validated['doctor_id'])
            ->forDate($date)
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED])
            ->get();

        // Generate available slots
        $slotDuration = $availability->slot_duration_mins;
        $startTime = Carbon::parse($date->toDateString() . ' ' . $availability->start_time);
        $endTime = Carbon::parse($date->toDateString() . ' ' . $availability->end_time);

        $slots = [];
        $current = $startTime->copy();

        while ($current->addMinutes($slotDuration)->lte($endTime)) {
            $slotStart = $current->copy()->subMinutes($slotDuration);
            $slotEnd = $current->copy();

            // Check if slot is available
            $isBooked = $existingAppointments->contains(function ($appt) use ($slotStart, $slotEnd) {
                $apptStart = Carbon::parse($appt->scheduled_at);
                $apptEnd = $apptStart->copy()->addMinutes($appt->duration_mins);
                return $slotStart->lt($apptEnd) && $slotEnd->gt($apptStart);
            });

            if (!$isBooked && $slotStart->gt(now())) {
                $slots[] = [
                    'start' => $slotStart->toIso8601String(),
                    'end' => $slotEnd->toIso8601String(),
                    'display' => $slotStart->format('h:i A'),
                ];
            }
        }

        Log::info('Available slots generated', ['count' => count($slots)]);

        return response()->json([
            'slots' => $slots,
            'doctor_id' => $validated['doctor_id'],
            'date' => $date->toDateString(),
        ]);
    }

    /**
     * Get queue (today's appointments)
     */
    public function queue(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching queue', ['clinic_id' => $clinicId]);

        $appointments = Appointment::forClinic($clinicId)
            ->today()
            ->with(['patient', 'doctor', 'service'])
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED])
            ->orderBy('scheduled_at', 'asc')
            ->get();

        $queue = [
            'waiting' => $appointments->where('status', Appointment::STATUS_CHECKED_IN)->values(),
            'in_consultation' => $appointments->where('status', Appointment::STATUS_IN_CONSULTATION)->values(),
            'upcoming' => $appointments->whereIn('status', [Appointment::STATUS_BOOKED, Appointment::STATUS_CONFIRMED])->values(),
            'completed' => $appointments->where('status', Appointment::STATUS_COMPLETED)->values(),
        ];

        Log::info('Queue retrieved', [
            'waiting' => $queue['waiting']->count(),
            'in_consultation' => $queue['in_consultation']->count(),
            'upcoming' => $queue['upcoming']->count(),
        ]);

        return response()->json([
            'queue' => $queue,
        ]);
    }

    /**
     * Public slots (for online booking)
     */
    public function publicSlots(Request $request, string $clinicSlug): JsonResponse
    {
        Log::info('Public slots request', ['clinic_slug' => $clinicSlug]);

        $clinic = Clinic::where('slug', $clinicSlug)->active()->firstOrFail();

        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        // Similar logic to availableSlots but public
        $date = Carbon::parse($validated['date']);
        $dayOfWeek = $date->dayOfWeek;

        $availability = DoctorAvailability::where('clinic_id', $clinic->id)
            ->forDoctor($validated['doctor_id'])
            ->forDay($dayOfWeek)
            ->active()
            ->effectiveOn($date)
            ->first();

        if (!$availability) {
            return response()->json(['slots' => []]);
        }

        $existingAppointments = Appointment::forClinic($clinic->id)
            ->forDoctor($validated['doctor_id'])
            ->forDate($date)
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED])
            ->get();

        $slotDuration = $availability->slot_duration_mins;
        $startTime = Carbon::parse($date->toDateString() . ' ' . $availability->start_time);
        $endTime = Carbon::parse($date->toDateString() . ' ' . $availability->end_time);

        $slots = [];
        $current = $startTime->copy();

        while ($current->addMinutes($slotDuration)->lte($endTime)) {
            $slotStart = $current->copy()->subMinutes($slotDuration);
            $slotEnd = $current->copy();

            $isBooked = $existingAppointments->contains(function ($appt) use ($slotStart, $slotEnd) {
                $apptStart = Carbon::parse($appt->scheduled_at);
                $apptEnd = $apptStart->copy()->addMinutes($appt->duration_mins);
                return $slotStart->lt($apptEnd) && $slotEnd->gt($apptStart);
            });

            if (!$isBooked && $slotStart->gt(now())) {
                $slots[] = [
                    'start' => $slotStart->toIso8601String(),
                    'end' => $slotEnd->toIso8601String(),
                    'display' => $slotStart->format('h:i A'),
                ];
            }
        }

        return response()->json(['slots' => $slots]);
    }

    /**
     * Public booking
     */
    public function publicBook(Request $request, string $clinicSlug): JsonResponse
    {
        Log::info('Public booking request', ['clinic_slug' => $clinicSlug]);

        $clinic = Clinic::where('slug', $clinicSlug)->active()->firstOrFail();

        $validated = $request->validate([
            'patient_name' => 'required|string|max:200',
            'patient_phone' => 'required|string|max:15',
            'patient_email' => 'nullable|email',
            'doctor_id' => 'required|exists:users,id',
            'scheduled_at' => 'required|date|after:now',
            'specialty' => 'required|string|max:50',
        ]);

        // Find or create patient
        $patient = Patient::firstOrCreate(
            ['clinic_id' => $clinic->id, 'phone' => $validated['patient_phone']],
            [
                'name' => $validated['patient_name'],
                'email' => $validated['patient_email'] ?? null,
                'source' => 'online_booking',
            ]
        );

        // Create appointment
        $todayCount = Appointment::forClinic($clinic->id)
            ->whereDate('scheduled_at', Carbon::parse($validated['scheduled_at'])->toDateString())
            ->count();

        $appointment = Appointment::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'doctor_id' => $validated['doctor_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'specialty' => $validated['specialty'],
            'status' => Appointment::STATUS_BOOKED,
            'token_number' => $todayCount + 1,
            'booking_source' => 'online_booking',
            'appointment_type' => 'new',
            'duration_mins' => 15,
        ]);

        Log::info('Public booking created', ['appointment_id' => $appointment->id]);

        return response()->json([
            'message' => 'Appointment booked successfully',
            'appointment_id' => $appointment->id,
            'token_number' => $appointment->token_number,
        ], 201);
    }

    /**
     * Confirm public booking
     */
    public function publicConfirm(Request $request, string $clinicSlug): JsonResponse
    {
        Log::info('Public booking confirmation', ['clinic_slug' => $clinicSlug]);

        $clinic = Clinic::where('slug', $clinicSlug)->active()->firstOrFail();

        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'otp' => 'required|string|size:6',
        ]);

        $appointment = Appointment::forClinic($clinic->id)->findOrFail($validated['appointment_id']);

        // TODO: Verify OTP
        // For now, just confirm
        $appointment->update([
            'status' => Appointment::STATUS_CONFIRMED,
            'confirmation_sent_at' => now(),
        ]);

        Log::info('Public booking confirmed', ['appointment_id' => $appointment->id]);

        return response()->json([
            'message' => 'Appointment confirmed',
            'appointment' => $appointment,
        ]);
    }
}
