<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public Booking Controller
 * Handles online appointment booking for patients (no auth required)
 * URL: clinicname.clinicos.in/book or /book/clinic-slug
 */
class PublicBookingController extends Controller
{
    /**
     * Patient-facing hub: list clinics that accept online booking (no login).
     * URL: /book — not a static file under public/; Laravel serves this route.
     */
    public function directory(): View
    {
        Log::info('PublicBookingController: patient booking directory (/book)');

        $clinics = Clinic::query()
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('name')
            ->withCount([
                'users as public_doctors_count' => function ($q) {
                    $q->whereIn('role', ['doctor', 'owner'])
                        ->where('is_active', true);
                },
            ])
            ->get()
            ->filter(function (Clinic $c) {
                try {
                    return data_get($c->settings, 'public_booking_enabled', true) !== false;
                } catch (\Throwable $e) {
                    Log::warning('PublicBookingController: directory skip settings read', [
                        'clinic_id' => $c->id,
                        'error' => $e->getMessage(),
                    ]);

                    return true;
                }
            })
            ->values();

        Log::info('PublicBookingController: directory clinics loaded', ['count' => $clinics->count()]);

        return view('public.booking-directory', compact('clinics'));
    }

    /**
     * Show the public booking page for a clinic
     */
    public function show(string $clinicSlug): View
    {
        Log::info('PublicBookingController: Loading booking page for clinic', ['slug' => $clinicSlug]);

        $clinic = Clinic::where('slug', $clinicSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $doctorCols = ['id', 'name', 'specialty', 'qualification'];
        if (Schema::hasTable('users')) {
            foreach (['specialty', 'qualification'] as $col) {
                if (! Schema::hasColumn('users', $col)) {
                    $doctorCols = array_values(array_diff($doctorCols, [$col]));
                }
            }
        }

        $doctors = collect();
        if (Schema::hasTable('users')) {
            $doctors = User::where('clinic_id', $clinic->id)
                ->whereIn('role', ['doctor', 'owner'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(count($doctorCols) ? $doctorCols : ['id', 'name']);
        }

        // Core schema has no `description` on appointment_services; selecting it causes 500 on MySQL.
        $serviceCols = ['id', 'name', 'duration_mins', 'advance_amount'];
        if (Schema::hasTable('appointment_services') && Schema::hasColumn('appointment_services', 'description')) {
            $serviceCols[] = 'description';
        }

        $services = collect();
        if (Schema::hasTable('appointment_services')) {
            $services = AppointmentService::where('clinic_id', $clinic->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get($serviceCols);
        }

        $settings = $this->safeClinicSettings($clinic);
        $bookingSettings = [
            'advance_days' => $settings['booking_advance_days'] ?? 30,
            'slot_duration' => $settings['slot_duration_mins'] ?? 15,
            'start_time' => $settings['clinic_start_time'] ?? '09:00',
            'end_time' => $settings['clinic_end_time'] ?? '20:00',
            'break_start' => $settings['break_start_time'] ?? '13:00',
            'break_end' => $settings['break_end_time'] ?? '14:00',
            'require_advance' => (bool) ($settings['require_advance_payment'] ?? false),
            'min_advance' => (float) ($settings['min_advance_amount'] ?? 0),
        ];

        Log::info('PublicBookingController: Booking page data loaded', [
            'clinic_id' => $clinic->id,
            'doctors' => $doctors->count(),
            'services' => $services->count(),
        ]);

        return view('public.booking', compact('clinic', 'doctors', 'services', 'bookingSettings'));
    }

    /**
     * Avoid 500 when clinics.settings is invalid JSON (Eloquent cast would throw).
     *
     * @return array<string, mixed>
     */
    private function safeClinicSettings(Clinic $clinic): array
    {
        try {
            $s = $clinic->settings;

            return is_array($s) ? $s : [];
        } catch (\Throwable $e) {
            Log::warning('PublicBookingController: clinic settings unreadable, using defaults', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get available time slots for a specific date and doctor
     */
    public function getAvailableSlots(Request $request, string $clinicSlug): JsonResponse
    {
        Log::info('PublicBookingController: Getting available slots', ['slug' => $clinicSlug, 'date' => $request->date]);

        $clinic = Clinic::where('slug', $clinicSlug)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'doctor_id' => 'nullable|exists:users,id',
            'service_id' => 'nullable|exists:appointment_services,id',
        ]);

        $date = Carbon::parse($validated['date']);
        $doctorId = $validated['doctor_id'] ?? null;
        $serviceId = $validated['service_id'] ?? null;

        $settings = $clinic->settings ?? [];
        $slotDuration = $settings['slot_duration_mins'] ?? 15;
        $startTime = $settings['clinic_start_time'] ?? '09:00';
        $endTime = $settings['clinic_end_time'] ?? '20:00';
        $breakStart = $settings['break_start_time'] ?? '13:00';
        $breakEnd = $settings['break_end_time'] ?? '14:00';

        $existingAppointments = Appointment::where('clinic_id', $clinic->id)
            ->whereDate('scheduled_at', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->pluck('scheduled_at')
            ->map(fn($dt) => Carbon::parse($dt)->format('H:i'))
            ->toArray();

        $slots = [];
        $current = Carbon::parse($date->format('Y-m-d') . ' ' . $startTime);
        $end = Carbon::parse($date->format('Y-m-d') . ' ' . $endTime);
        $breakStartTime = Carbon::parse($date->format('Y-m-d') . ' ' . $breakStart);
        $breakEndTime = Carbon::parse($date->format('Y-m-d') . ' ' . $breakEnd);

        while ($current < $end) {
            $timeStr = $current->format('H:i');
            
            $isBreakTime = $current >= $breakStartTime && $current < $breakEndTime;
            $isPast = $date->isToday() && $current < now();
            $isBooked = in_array($timeStr, $existingAppointments);
            
            $slots[] = [
                'time' => $timeStr,
                'display' => $current->format('h:i A'),
                'available' => !$isBreakTime && !$isPast && !$isBooked,
                'reason' => $isBreakTime ? 'break' : ($isPast ? 'past' : ($isBooked ? 'booked' : null)),
            ];

            $current->addMinutes($slotDuration);
        }

        Log::info('PublicBookingController: Slots generated', ['date' => $date->toDateString(), 'total' => count($slots)]);

        return response()->json([
            'date' => $date->toDateString(),
            'slots' => $slots,
            'available_count' => collect($slots)->where('available', true)->count(),
        ]);
    }

    /**
     * Create a booking (public, no auth)
     */
    public function book(Request $request, string $clinicSlug): JsonResponse
    {
        Log::info('PublicBookingController: Creating booking', ['slug' => $clinicSlug, 'data' => $request->except('phone')]);

        $clinic = Clinic::where('slug', $clinicSlug)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'patient_name' => 'required|string|max:200',
            'patient_phone' => 'required|string|max:15',
            'patient_email' => 'nullable|email|max:150',
            'patient_dob' => 'nullable|date',
            'patient_gender' => 'nullable|in:male,female,other',
            'doctor_id' => 'required|exists:users,id',
            'service_id' => 'nullable|exists:appointment_services,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'appointment_type' => 'nullable|in:new,followup,procedure,teleconsultation',
            'notes' => 'nullable|string|max:500',
            'razorpay_payment_id' => 'nullable|string',
            'razorpay_order_id' => 'nullable|string',
            'razorpay_signature' => 'nullable|string',
            'advance_amount_paid' => 'nullable|numeric|min:0',
        ]);

        $expectedAdvance = $this->resolveExpectedAdvanceAmount($clinic, $validated['service_id'] ?? null);
        $hasPaymentProof = !empty($validated['razorpay_payment_id']) && !empty($validated['razorpay_order_id']);

        Log::info('PublicBookingController: book payment gate', [
            'expected_advance' => $expectedAdvance,
            'has_payment_proof' => $hasPaymentProof,
            'clinic_id' => $clinic->id,
        ]);

        if ($expectedAdvance >= 1) {
            if (!$hasPaymentProof) {
                return response()->json([
                    'success' => false,
                    'error' => 'Advance payment is required for this booking. Please complete payment.',
                ], 422);
            }

            $sig = (string) ($validated['razorpay_signature'] ?? '');
            if ($sig === '' || !$this->verifyRazorpayPaymentSignature(
                $validated['razorpay_order_id'],
                $validated['razorpay_payment_id'],
                $sig
            )) {
                Log::warning('PublicBookingController: Razorpay signature verification failed', [
                    'clinic_id' => $clinic->id,
                    'order_id' => $validated['razorpay_order_id'] ?? null,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Payment could not be verified. Please try again or contact the clinic.',
                ], 400);
            }
        } elseif ($hasPaymentProof) {
            $sig = (string) ($validated['razorpay_signature'] ?? '');
            if ($sig !== '' && !$this->verifyRazorpayPaymentSignature(
                $validated['razorpay_order_id'],
                $validated['razorpay_payment_id'],
                $sig
            )) {
                Log::warning('PublicBookingController: optional payment signature failed');

                return response()->json([
                    'success' => false,
                    'error' => 'Payment could not be verified.',
                ], 400);
            }
        }

        try {
            DB::beginTransaction();

            $patient = Patient::firstOrCreate(
                [
                    'clinic_id' => $clinic->id,
                    'phone' => $validated['patient_phone'],
                ],
                [
                    'name' => $validated['patient_name'],
                    'email' => $validated['patient_email'] ?? null,
                    'dob' => $validated['patient_dob'] ?? null,
                    'gender' => $validated['patient_gender'] ?? null,
                ]
            );

            $scheduledAt = Carbon::parse($validated['appointment_date'] . ' ' . $validated['appointment_time']);

            $existing = Appointment::where('clinic_id', $clinic->id)
                ->where('doctor_id', $validated['doctor_id'])
                ->where('scheduled_at', $scheduledAt)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->exists();

            if ($existing) {
                DB::rollBack();
                return response()->json(['success' => false, 'error' => 'This slot is no longer available. Please select another time.'], 409);
            }

            $doctor = User::find($validated['doctor_id']);
            $service = $validated['service_id'] ? AppointmentService::find($validated['service_id']) : null;

            $preVisitToken = Str::random(48);

            $appointment = Appointment::create([
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'doctor_id' => $validated['doctor_id'],
                'service_id' => $validated['service_id'] ?? null,
                'scheduled_at' => $scheduledAt,
                'duration_mins' => $service->duration_mins ?? 30,
                'appointment_type' => $validated['appointment_type'] ?? 'new',
                'specialty' => $doctor->specialty ?? 'general',
                'notes' => $validated['notes'] ?? null,
                'status' => 'confirmed',
                'booking_source' => 'online_booking',
                'advance_paid' => $hasPaymentProof
                    ? ($expectedAdvance >= 1 ? $expectedAdvance : (float) ($validated['advance_amount_paid'] ?? 0))
                    : 0,
                'razorpay_payment_id' => $validated['razorpay_payment_id'] ?? null,
                'razorpay_order_id' => $validated['razorpay_order_id'] ?? null,
                'pre_visit_token' => $preVisitToken,
            ]);

            DB::commit();

            $preVisitUrl = url('/book/' . $clinic->slug . '/pre-visit/' . $preVisitToken);

            Log::info('PublicBookingController: Booking created', [
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
                'pre_visit_url_len' => strlen($preVisitUrl),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment booked successfully!',
                'pre_visit_url' => $preVisitUrl,
                'appointment' => [
                    'id' => $appointment->id,
                    'date' => $scheduledAt->format('l, d M Y'),
                    'time' => $scheduledAt->format('h:i A'),
                    'doctor' => $doctor->name,
                    'clinic' => $clinic->name,
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PublicBookingController: Booking failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Could not complete booking. Please try again.'], 500);
        }
    }

    /**
     * Create a Razorpay order for advance payment
     */
    public function createPaymentOrder(Request $request, string $clinicSlug): JsonResponse
    {
        Log::info('PublicBookingController: Creating payment order', ['slug' => $clinicSlug]);

        $clinic = Clinic::where('slug', $clinicSlug)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'service_id' => 'nullable|exists:appointment_services,id',
            'patient_name' => 'required|string|max:200',
            'patient_phone' => 'required|string|max:15',
        ]);

        $expected = $this->resolveExpectedAdvanceAmount($clinic, $validated['service_id'] ?? null);
        Log::info('PublicBookingController: createPaymentOrder expected advance', [
            'clinic_id' => $clinic->id,
            'expected' => $expected,
            'sent_amount' => $validated['amount'],
        ]);

        if ($expected < 1) {
            return response()->json([
                'success' => false,
                'error' => 'No advance payment is required for this booking. You can confirm without paying.',
            ], 422);
        }

        if (abs((float) $validated['amount'] - $expected) > 0.05) {
            return response()->json([
                'success' => false,
                'error' => 'Amount must be ₹'.number_format($expected, 2).' for this booking.',
                'expected_amount' => $expected,
            ], 422);
        }

        $razorpayKeyId = config('services.razorpay.key_id');
        $razorpaySecret = config('services.razorpay.secret');

        if (!$razorpayKeyId || !$razorpaySecret) {
            Log::warning('PublicBookingController: Razorpay not configured', ['clinic_id' => $clinic->id]);
            return response()->json(['success' => false, 'error' => 'Payment gateway not configured'], 503);
        }

        try {
            $api = new \Razorpay\Api\Api($razorpayKeyId, $razorpaySecret);

            $orderData = [
                'receipt' => 'booking_' . time(),
                'amount' => $validated['amount'] * 100,
                'currency' => 'INR',
                'notes' => [
                    'clinic_id' => $clinic->id,
                    'clinic_name' => $clinic->name,
                    'patient_name' => $validated['patient_name'],
                    'patient_phone' => $validated['patient_phone'],
                    'type' => 'appointment_advance',
                ],
            ];

            $order = $api->order->create($orderData);

            Log::info('PublicBookingController: Payment order created', ['order_id' => $order->id]);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'amount' => $validated['amount'],
                'currency' => 'INR',
                'key_id' => $razorpayKeyId,
                'clinic_name' => $clinic->name,
            ]);

        } catch (\Throwable $e) {
            Log::error('PublicBookingController: Payment order creation failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Could not initiate payment. Please try again.'], 500);
        }
    }

    /**
     * Verify Razorpay payment
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        Log::info('PublicBookingController: Verifying payment');

        $validated = $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $razorpaySecret = config('services.razorpay.secret');

        $generatedSignature = hash_hmac(
            'sha256',
            $validated['razorpay_order_id'] . '|' . $validated['razorpay_payment_id'],
            $razorpaySecret
        );

        if ($generatedSignature === $validated['razorpay_signature']) {
            Log::info('PublicBookingController: Payment verified', ['payment_id' => $validated['razorpay_payment_id']]);
            return response()->json(['success' => true, 'verified' => true]);
        }

        Log::warning('PublicBookingController: Payment verification failed', ['payment_id' => $validated['razorpay_payment_id']]);
        return response()->json(['success' => false, 'verified' => false, 'error' => 'Payment verification failed'], 400);
    }

    /**
     * Public pre-visit questionnaire (token in URL; no login).
     */
    public function showPreVisit(string $clinicSlug, string $token): View
    {
        Log::info('PublicBookingController: showPreVisit', [
            'slug' => $clinicSlug,
            'token_len' => strlen($token),
        ]);

        $clinic = Clinic::where('slug', $clinicSlug)->where('is_active', true)->firstOrFail();

        $appointment = Appointment::where('pre_visit_token', $token)
            ->where('clinic_id', $clinic->id)
            ->with(['service', 'patient'])
            ->firstOrFail();

        $questions = $this->normalizePreVisitQuestions($appointment);

        return view('public.pre-visit', compact('clinic', 'appointment', 'questions'));
    }

    public function submitPreVisit(Request $request, string $clinicSlug, string $token): RedirectResponse
    {
        Log::info('PublicBookingController: submitPreVisit', ['slug' => $clinicSlug, 'token_len' => strlen($token)]);

        $clinic = Clinic::where('slug', $clinicSlug)->where('is_active', true)->firstOrFail();

        $appointment = Appointment::where('pre_visit_token', $token)
            ->where('clinic_id', $clinic->id)
            ->firstOrFail();

        $questions = $this->normalizePreVisitQuestions($appointment);
        $rules = [];
        foreach ($questions as $q) {
            $rules['answer_' . $q['id']] = 'nullable|string|max:2000';
        }

        $validated = $request->validate($rules);

        $answers = [];
        foreach ($questions as $q) {
            $key = 'answer_' . $q['id'];
            $answers[$q['id']] = $validated[$key] ?? '';
        }

        $appointment->update([
            'pre_visit_answers' => $answers,
            'pre_visit_data' => array_merge($appointment->pre_visit_data ?? [], [
                'submitted_at' => now()->toIso8601String(),
                'source' => 'public_pre_visit',
            ]),
        ]);

        Log::info('PublicBookingController: pre-visit answers saved', [
            'appointment_id' => $appointment->id,
            'answer_keys' => array_keys($answers),
        ]);

        return redirect()
            ->route('public.booking.pre-visit', ['clinicSlug' => $clinicSlug, 'token' => $token])
            ->with('success', 'Thank you — your responses were saved.');
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    private function normalizePreVisitQuestions(Appointment $appointment): array
    {
        $raw = $appointment->service?->pre_visit_questions;
        if (empty($raw) || !is_array($raw)) {
            Log::info('PublicBookingController: using default pre-visit questions', [
                'appointment_id' => $appointment->id,
            ]);

            return [
                ['id' => 'reason', 'label' => 'What is the main reason for your visit?'],
                ['id' => 'medications', 'label' => 'Current medications (or "none")'],
                ['id' => 'allergies', 'label' => 'Known allergies (or "none")'],
            ];
        }

        $out = [];
        foreach ($raw as $i => $item) {
            if (is_string($item)) {
                $out[] = ['id' => 'q' . $i, 'label' => $item];
            } elseif (is_array($item) && !empty($item['label'])) {
                $out[] = [
                    'id' => (string) ($item['id'] ?? ('q' . $i)),
                    'label' => $item['label'],
                ];
            }
        }

        Log::info('PublicBookingController: normalized pre-visit questions', [
            'appointment_id' => $appointment->id,
            'count' => count($out),
        ]);

        return $out;
    }

    /**
     * Rupee amount patient must pay before booking (clinic floor vs service advance).
     */
    private function resolveExpectedAdvanceAmount(Clinic $clinic, ?int $serviceId): float
    {
        $settings = $clinic->settings ?? [];
        $require = filter_var($settings['require_advance_payment'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $clinicMin = $require ? (float) ($settings['min_advance_amount'] ?? 0) : 0.0;

        $svcAmt = 0.0;
        if ($serviceId) {
            $service = AppointmentService::where('clinic_id', $clinic->id)
                ->where('id', $serviceId)
                ->first();
            if ($service) {
                $svcAmt = (float) ($service->advance_amount ?? 0);
            }
        }

        $out = round(max($clinicMin, $svcAmt), 2);
        Log::debug('PublicBookingController: resolveExpectedAdvanceAmount', [
            'clinic_id' => $clinic->id,
            'service_id' => $serviceId,
            'clinic_min' => $clinicMin,
            'service_advance' => $svcAmt,
            'resolved' => $out,
        ]);

        return $out;
    }

    private function verifyRazorpayPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $secret = config('services.razorpay.secret');
        if ($secret === null || $secret === '' || $signature === '') {
            Log::warning('PublicBookingController: verifyRazorpayPaymentSignature missing secret or signature');

            return false;
        }

        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $secret);

        return hash_equals($expected, $signature);
    }
}
