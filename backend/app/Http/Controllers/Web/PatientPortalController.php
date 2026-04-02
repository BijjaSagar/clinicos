<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatientPortalController extends Controller
{
    // ── Login / OTP ────────────────────────────────────────────────────────

    public function showLogin()
    {
        if (session('patient_portal_id')) {
            return redirect()->route('patient-portal.dashboard');
        }
        return view('patient-portal.login');
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string|min:10|max:15']);

        $phone = preg_replace('/[^0-9]/', '', $request->phone);

        $patient = DB::table('patients')
            ->where(DB::raw("REGEXP_REPLACE(phone, '[^0-9]', '')"), $phone)
            ->orWhere(DB::raw("REGEXP_REPLACE(phone_alt, '[^0-9]', '')"), $phone)
            ->first();

        if (!$patient) {
            return back()->with('error', 'No patient found with this phone number.');
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(10);

        DB::table('patients')
            ->where('id', $patient->id)
            ->update(['portal_otp' => $otp, 'portal_otp_expires_at' => $expires]);

        // Log OTP (in production, send via SMS/WhatsApp)
        Log::info("Patient portal OTP for {$patient->name} (id:{$patient->id}): {$otp}");

        // Attempt to queue WhatsApp OTP message
        try {
            DB::table('notification_queue')->insert([
                'clinic_id'    => $patient->clinic_id,
                'patient_id'   => $patient->id,
                'type'         => 'custom',
                'channel'      => 'whatsapp',
                'phone_number' => $patient->phone,
                'message'      => "Your ClinicOS portal login OTP is: *{$otp}*\n\nValid for 10 minutes. Do not share.",
                'status'       => 'pending',
                'scheduled_at' => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Could not queue OTP WhatsApp message', ['error' => $e->getMessage()]);
        }

        session(['patient_portal_otp_patient_id' => $patient->id]);

        return back()->with([
            'otp_sent' => true,
            'patient_name' => $patient->name,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|string|size:6']);

        $patientId = session('patient_portal_otp_patient_id');
        if (!$patientId) {
            return redirect()->route('patient-portal.login')->with('error', 'Session expired. Please request a new OTP.');
        }

        $patient = DB::table('patients')->where('id', $patientId)->first();

        if (!$patient || $patient->portal_otp !== $request->otp) {
            return back()->with('error', 'Invalid OTP. Please try again.');
        }

        if (now()->isAfter($patient->portal_otp_expires_at)) {
            return back()->with('error', 'OTP has expired. Please request a new one.');
        }

        // Clear OTP
        DB::table('patients')->where('id', $patientId)->update([
            'portal_otp'            => null,
            'portal_otp_expires_at' => null,
        ]);

        session()->forget('patient_portal_otp_patient_id');
        session(['patient_portal_id' => $patientId]);

        return redirect()->route('patient-portal.dashboard');
    }

    public function logout()
    {
        session()->forget('patient_portal_id');
        return redirect()->route('patient-portal.login')->with('success', 'You have been logged out.');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function currentPatient(): object
    {
        return DB::table('patients')->where('id', session('patient_portal_id'))->firstOrFail();
    }

    // ── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $patient = $this->currentPatient();

        $appointments = DB::table('appointments')
            ->where('patient_id', $patient->id)
            ->orderByDesc('scheduled_at')
            ->limit(5)
            ->get();

        $latestInvoice = DB::table('invoices')
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->first();

        $pendingBalance = DB::table('invoices')
            ->where('patient_id', $patient->id)
            ->whereIn('payment_status', ['pending', 'partial'])
            ->selectRaw('SUM(total - paid) as balance')
            ->value('balance') ?? 0;

        $labReportCount = DB::table('lab_orders')
            ->where('patient_id', $patient->id)
            ->where('status', 'resulted')
            ->count();

        return view('patient-portal.dashboard', compact(
            'patient', 'appointments', 'latestInvoice', 'pendingBalance', 'labReportCount'
        ));
    }

    // ── Appointments ─────────────────────────────────────────────────────────

    public function appointments()
    {
        $patient = $this->currentPatient();

        $appointments = DB::table('appointments')
            ->where('patient_id', $patient->id)
            ->orderByDesc('scheduled_at')
            ->paginate(15);

        return view('patient-portal.appointments', compact('patient', 'appointments'));
    }

    // ── Prescriptions ─────────────────────────────────────────────────────────

    public function prescriptions()
    {
        $patient = $this->currentPatient();

        $visits = DB::table('visits')
            ->where('patient_id', $patient->id)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('prescriptions')
                    ->whereColumn('prescriptions.visit_id', 'visits.id');
            })
            ->orderByDesc('visit_date')
            ->paginate(15);

        return view('patient-portal.prescriptions', compact('patient', 'visits'));
    }

    public function prescriptionPdf(int $visitId)
    {
        $patient = $this->currentPatient();

        $visit = DB::table('visits')->where('id', $visitId)->where('patient_id', $patient->id)->firstOrFail();

        $drugs = DB::table('prescriptions')
            ->where('visit_id', $visitId)
            ->get();

        $clinic = DB::table('clinics')->where('id', $patient->clinic_id)->first();

        try {
            $pdf = Pdf::loadView('patient-portal.prescription-pdf', compact('patient', 'visit', 'drugs', 'clinic'));
            return $pdf->download("prescription-{$visitId}.pdf");
        } catch (\Throwable $e) {
            Log::error('Portal prescription PDF failed', ['error' => $e->getMessage()]);
            return view('patient-portal.prescription-pdf', compact('patient', 'visit', 'drugs', 'clinic'));
        }
    }

    // ── Lab Reports ────────────────────────────────────────────────────────────

    public function labReports()
    {
        $patient = $this->currentPatient();

        $orders = DB::table('lab_orders')
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('patient-portal.lab-reports', compact('patient', 'orders'));
    }

    public function labReportPdf(int $orderId)
    {
        $patient = $this->currentPatient();

        $order = DB::table('lab_orders')->where('id', $orderId)->where('patient_id', $patient->id)->firstOrFail();

        $results = DB::table('lab_results')->where('lab_order_id', $orderId)->get();

        $clinic = DB::table('clinics')->where('id', $patient->clinic_id)->first();

        try {
            $pdf = Pdf::loadView('patient-portal.lab-report-pdf', compact('patient', 'order', 'results', 'clinic'));
            return $pdf->download("lab-report-{$orderId}.pdf");
        } catch (\Throwable $e) {
            Log::error('Portal lab report PDF failed', ['error' => $e->getMessage()]);
            return view('patient-portal.lab-report-pdf', compact('patient', 'order', 'results', 'clinic'));
        }
    }

    // ── Invoices ────────────────────────────────────────────────────────────────

    public function invoices()
    {
        $patient = $this->currentPatient();

        $invoices = DB::table('invoices')
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('patient-portal.invoices', compact('patient', 'invoices'));
    }
}
