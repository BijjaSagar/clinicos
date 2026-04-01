<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Patient Mobile App API Controller
 * 
 * API endpoints for the Flutter patient mobile app.
 * Provides patient-facing features:
 * - Authentication (OTP-based)
 * - View appointments & book new ones
 * - View medical records
 * - View & pay invoices
 * - Manage ABHA ID
 */
class PatientAppController extends Controller
{
    /**
     * Request OTP for phone number
     */
    public function requestOtp(Request $request): JsonResponse
    {
        Log::info('PatientAppController: OTP requested', ['phone' => $request->input('phone')]);

        $validated = $request->validate([
            'phone' => 'required|string|size:10',
            'clinic_id' => 'required|exists:clinics,id',
        ]);

        $patient = Patient::where('clinic_id', $validated['clinic_id'])
            ->where('phone', $validated['phone'])
            ->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'error' => 'No patient found with this phone number',
                'code' => 'PATIENT_NOT_FOUND',
            ], 404);
        }

        // Generate OTP (in production, send via SMS)
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store OTP (in production, use cache/redis with expiry)
        $patient->update([
            'app_otp' => Hash::make($otp),
            'app_otp_expires' => now()->addMinutes(5),
        ]);

        Log::info('PatientAppController: OTP generated', ['patient_id' => $patient->id, 'otp' => $otp]);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your phone',
            'expires_in' => 300,
            // In development only:
            'debug_otp' => config('app.debug') ? $otp : null,
        ]);
    }

    /**
     * Verify OTP and get access token
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        Log::info('PatientAppController: Verifying OTP');

        $validated = $request->validate([
            'phone' => 'required|string|size:10',
            'clinic_id' => 'required|exists:clinics,id',
            'otp' => 'required|string|size:6',
        ]);

        $patient = Patient::where('clinic_id', $validated['clinic_id'])
            ->where('phone', $validated['phone'])
            ->first();

        if (!$patient || !$patient->app_otp) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid request',
            ], 400);
        }

        if ($patient->app_otp_expires && $patient->app_otp_expires < now()) {
            return response()->json([
                'success' => false,
                'error' => 'OTP expired',
                'code' => 'OTP_EXPIRED',
            ], 400);
        }

        if (!Hash::check($validated['otp'], $patient->app_otp)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid OTP',
                'code' => 'INVALID_OTP',
            ], 400);
        }

        // Clear OTP
        $patient->update([
            'app_otp' => null,
            'app_otp_expires' => null,
        ]);

        // Generate API token
        $token = $patient->createToken('patient-app', ['patient'])->plainTextToken;

        Log::info('PatientAppController: OTP verified', ['patient_id' => $patient->id]);

        return response()->json([
            'success' => true,
            'token' => $token,
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'email' => $patient->email,
                'dob' => $patient->dob?->format('Y-m-d'),
                'gender' => $patient->gender,
                'abha_id' => $patient->abha_id,
                'avatar_url' => $patient->avatar_url,
            ],
        ]);
    }

    /**
     * Get patient profile
     */
    public function profile(Request $request): JsonResponse
    {
        $patient = $this->getAuthenticatedPatient($request);
        
        $clinic = $patient->clinic;

        return response()->json([
            'success' => true,
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'email' => $patient->email,
                'dob' => $patient->dob?->format('Y-m-d'),
                'gender' => $patient->gender,
                'blood_group' => $patient->blood_group,
                'address' => $patient->address,
                'abha_id' => $patient->abha_id,
                'abha_address' => $patient->abha_address,
                'emergency_contact' => $patient->emergency_contact,
                'avatar_url' => $patient->avatar_url,
            ],
            'clinic' => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'phone' => $clinic->phone,
                'address' => $clinic->address_line1 . ($clinic->city ? ', ' . $clinic->city : ''),
                'logo_url' => $clinic->logo_url,
            ],
        ]);
    }

    /**
     * Update patient profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $patient = $this->getAuthenticatedPatient($request);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'email' => 'sometimes|nullable|email|max:150',
            'dob' => 'sometimes|nullable|date',
            'gender' => 'sometimes|nullable|in:male,female,other',
            'blood_group' => 'sometimes|nullable|string|max:10',
            'address' => 'sometimes|nullable|string|max:500',
            'emergency_contact' => 'sometimes|nullable|string|max:100',
        ]);

        $patient->update($validated);

        Log::info('PatientAppController: Profile updated', ['patient_id' => $patient->id]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated',
        ]);
    }

    /**
     * Get upcoming appointments
     */
    public function appointments(Request $request): JsonResponse
    {
        $patient = $this->getAuthenticatedPatient($request);

        $appointments = Appointment::with(['doctor:id,name,specialty'])
            ->where('patient_id', $patient->id)
            ->where('scheduled_at', '>=', now())
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get()
            ->map(fn($apt) => [
                'id' => $apt->id,
                'date' => $apt->scheduled_at->format('Y-m-d'),
                'time' => $apt->scheduled_at->format('H:i'),
                'display_date' => $apt->scheduled_at->format('l, d M Y'),
                'display_time' => $apt->scheduled_at->format('h:i A'),
                'doctor_name' => $apt->doctor->name ?? 'Doctor',
                'specialty' => $apt->doctor->specialty ?? null,
                'type' => $apt->appointment_type,
                'status' => $apt->status,
                'duration_mins' => $apt->duration_mins,
            ]);

        return response()->json([
            'success' => true,
            'appointments' => $appointments,
        ]);
    }

    /**
     * Get appointment history
     */
    public function appointmentHistory(Request $request): JsonResponse
    {
        $patient = $this->getAuthenticatedPatient($request);

        $appointments = Appointment::with(['doctor:id,name,specialty'])
            ->where('patient_id', $patient->id)
            ->where(function ($q) {
                $q->where('scheduled_at', '<', now())
                  ->orWhereIn('status', ['completed', 'cancelled', 'no_show']);
            })
            ->orderByDesc('scheduled_at')
            ->paginate(20)
            ->through(fn($apt) => [
                'id' => $apt->id,
                'date' => $apt->scheduled_at->format('Y-m-d'),
                'display_date' => $apt->scheduled_at->format('d M Y'),
                'display_time' => $apt->scheduled_at->format('h:i A'),
                'doctor_name' => $apt->doctor->name ?? 'Doctor',
                'specialty' => $apt->doctor->specialty ?? null,
                'status' => $apt->status,
            ]);

        return response()->json([
            'success' => true,
            'appointments' => $appointments,
        ]);
    }

    /**
     * Book a new appointment
     */
    public function bookAppointment(Request $request): JsonResponse
    {
        $patient = $this->getAuthenticatedPatient($request);

        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'type' => 'nullable|in:new,followup,procedure,teleconsultation',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $scheduledAt = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['time']);

            // Check slot availability
            $existing = Appointment::where('clinic_id', $patient->clinic_id)
                ->where('doctor_id', $validated['doctor_id'])
                ->where('scheduled_at', $scheduledAt)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->exists();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'error' => 'This slot is no longer available',
                    'code' => 'SLOT_UNAVAILABLE',
                ], 409);
            }

            $appointment = Appointment::create([
                'clinic_id' => $patient->clinic_id,
                'patient_id' => $patient->id,
                'doctor_id' => $validated['doctor_id'],
                'scheduled_at' => $scheduledAt,
                'duration_mins' => 30,
                'appointment_type' => $validated['type'] ?? 'followup',
                'notes' => $validated['notes'] ?? null,
                'status' => 'confirmed',
                'booking_source' => 'patient_app',
            ]);

            Log::info('PatientAppController: Appointment booked', ['appointment_id' => $appointment->id]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment booked successfully',
                'appointment' => [
                    'id' => $appointment->id,
                    'date' => $scheduledAt->format('l, d M Y'),
                    'time' => $scheduledAt->format('h:i A'),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('PatientAppController: Booking error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Booking failed'], 500);
        }
    }

    /**
     * Cancel an appointment
     */
    public function cancelAppointment(Request $request, Appointment $appointment): JsonResponse
    {
        $patient = $this->getAuthenticatedPatient($request);

        if ($appointment->patient_id !== $patient->id) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        if (!in_array($appointment->status, ['confirmed', 'checked_in'])) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot cancel this appointment',
            ], 400);
        }

        $appointment->update([
            'status' => 'cancelled',
            'cancelled_reason' => 'Cancelled by patient via app',
        ]);

        Log::info('PatientAppController: Appointment cancelled', ['appointment_id' => $appointment->id]);

        return response()->json([
            'success' => true,
            'message' => 'Appointment cancelled',
        ]);
    }

    /**
     * Get medical records (visits)
     */
    public function medicalRecords(Request $request): JsonResponse
    {
        $patient = $this->getAuthenticatedPatient($request);

        $visits = Visit::with(['doctor:id,name,specialty'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn($visit) => [
                'id' => $visit->id,
                'date' => $visit->created_at->format('d M Y'),
                'doctor_name' => $visit->doctor->name ?? 'Doctor',
                'specialty' => $visit->specialty ?? $visit->doctor->specialty ?? null,
                'diagnosis' => $visit->diagnosis,
                'chief_complaint' => $visit->chief_complaint,
                'has_prescription' => $visit->prescriptionItems()->exists(),
            ]);

        return response()->json([
            'success' => true,
            'records' => $visits,
        ]);
    }

    /**
     * Get visit details
     */
    public function visitDetails(Request $request, Visit $visit): JsonResponse
    {
        $patient = $this->getAuthenticatedPatient($request);

        if ($visit->patient_id !== $patient->id) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $visit->load(['doctor:id,name,specialty,qualification', 'prescriptionItems.drug']);

        return response()->json([
            'success' => true,
            'visit' => [
                'id' => $visit->id,
                'date' => $visit->created_at->format('d M Y'),
                'time' => $visit->created_at->format('h:i A'),
                'doctor' => [
                    'name' => $visit->doctor->name ?? 'Doctor',
                    'specialty' => $visit->doctor->specialty ?? null,
                    'qualification' => $visit->doctor->qualification ?? null,
                ],
                'vitals' => [
                    'bp' => $visit->bp_systolic ? $visit->bp_systolic . '/' . $visit->bp_diastolic : null,
                    'pulse' => $visit->pulse,
                    'temperature' => $visit->temperature,
                    'spo2' => $visit->spo2,
                    'weight' => $visit->weight,
                    'height' => $visit->height,
                ],
                'chief_complaint' => $visit->chief_complaint,
                'diagnosis' => $visit->diagnosis,
                'notes' => $visit->history_of_present_illness,
                'examination' => $visit->physical_examination,
                'plan' => $visit->plan,
                'prescription' => $visit->prescriptionItems->map(fn($item) => [
                    'drug_name' => $item->drug->generic_name ?? $item->drug_name,
                    'dosage' => $item->dosage,
                    'frequency' => $item->frequency,
                    'duration' => $item->duration_days . ' days',
                    'instructions' => $item->special_instructions,
                ]),
                'follow_up_date' => $visit->follow_up_date?->format('d M Y'),
            ],
        ]);
    }

    /**
     * Get invoices
     */
    public function invoices(Request $request): JsonResponse
    {
        $patient = $this->getAuthenticatedPatient($request);

        $invoices = Invoice::where('patient_id', $patient->id)
            ->orderByDesc('invoice_date')
            ->paginate(20)
            ->through(fn($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'date' => $inv->invoice_date->format('d M Y'),
                'total_amount' => $inv->total_amount,
                'paid_amount' => $inv->paid_amount ?? 0,
                'balance' => $inv->total_amount - ($inv->paid_amount ?? 0),
                'status' => $inv->payment_status,
                'payment_link' => $inv->payment_link,
            ]);

        return response()->json([
            'success' => true,
            'invoices' => $invoices,
        ]);
    }

    /**
     * Get invoice details
     */
    public function invoiceDetails(Request $request, Invoice $invoice): JsonResponse
    {
        $patient = $this->getAuthenticatedPatient($request);

        if ($invoice->patient_id !== $patient->id) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $invoice->load('items');

        return response()->json([
            'success' => true,
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'date' => $invoice->invoice_date->format('d M Y'),
                'items' => $invoice->items->map(fn($item) => [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total_price,
                ]),
                'subtotal' => $invoice->subtotal,
                'discount' => $invoice->discount_amount,
                'tax' => $invoice->tax_amount,
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount ?? 0,
                'balance' => $invoice->total_amount - ($invoice->paid_amount ?? 0),
                'status' => $invoice->payment_status,
                'payment_link' => $invoice->payment_link,
            ],
        ]);
    }

    /**
     * Link ABHA ID
     */
    public function linkAbha(Request $request): JsonResponse
    {
        $patient = $this->getAuthenticatedPatient($request);

        $validated = $request->validate([
            'abha_id' => 'required|string|max:20',
            'abha_address' => 'nullable|string|max:100',
        ]);

        $patient->update([
            'abha_id' => $validated['abha_id'],
            'abha_address' => $validated['abha_address'] ?? null,
            'abha_verified' => true,
        ]);

        Log::info('PatientAppController: ABHA linked', ['patient_id' => $patient->id]);

        return response()->json([
            'success' => true,
            'message' => 'ABHA ID linked successfully',
        ]);
    }

    /**
     * Get authenticated patient from request
     */
    private function getAuthenticatedPatient(Request $request): Patient
    {
        // In production, use Sanctum token
        $patientId = $request->user()?->id;
        
        if (!$patientId) {
            abort(401, 'Unauthorized');
        }

        return Patient::findOrFail($patientId);
    }
}
