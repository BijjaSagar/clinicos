<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Bed;
use App\Models\IpdAdmission;
use App\Models\IpdProgressNote;
use App\Models\IpdMedicationOrder;
use App\Models\IpdVital;
use App\Models\Patient;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class IpdController extends Controller
{
    // ─── Index ───────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $clinicId = auth()->user()->clinic_id;

        $query = IpdAdmission::with(['patient', 'bed', 'ward', 'primaryDoctor'])
            ->where('clinic_id', $clinicId)
            ->active();

        if ($search = $request->input('search')) {
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            })->orWhere('admission_number', 'like', "%{$search}%");
        }

        if ($wardId = $request->input('ward_id')) {
            $query->where('ward_id', $wardId);
        }

        $admissions = $query->orderByDesc('admission_date')->paginate(20)->withQueryString();

        // Stats
        $totalAdmitted = IpdAdmission::where('clinic_id', $clinicId)->active()->count();

        $availableBeds = Bed::where('clinic_id', $clinicId)->available()->count();

        $icuBedsAvailable = Bed::where('clinic_id', $clinicId)
            ->available()
            ->whereHas('ward', fn ($q) => $q->where('ward_type', 'icu'))
            ->count();

        $dischargesToday = IpdAdmission::where('clinic_id', $clinicId)
            ->dischargedToday()
            ->count();

        $stats = compact('totalAdmitted', 'availableBeds', 'icuBedsAvailable', 'dischargesToday');

        $wards = Ward::where('clinic_id', $clinicId)->active()->get();

        return view('ipd.index', compact('admissions', 'stats', 'wards'));
    }

    // ─── Bed Map ─────────────────────────────────────────────────────────────

    public function bedMap(): View
    {
        $clinicId = auth()->user()->clinic_id;

        $wards = Ward::with([
            'beds.currentAdmission.patient',
            'rooms',
        ])
            ->where('clinic_id', $clinicId)
            ->active()
            ->get();

        return view('ipd.bed-map', compact('wards'));
    }

    // ─── Create ──────────────────────────────────────────────────────────────

    public function create(): View
    {
        $clinicId = auth()->user()->clinic_id;

        $patients = Patient::where('clinic_id', $clinicId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'age_years', 'sex']);

        $doctors = User::where('clinic_id', $clinicId)
            ->whereIn('role', ['doctor', 'admin'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $availableBeds = Bed::with('ward')
            ->where('clinic_id', $clinicId)
            ->available()
            ->get()
            ->groupBy('ward.name');

        $wards = Ward::where('clinic_id', $clinicId)->active()->get();

        return view('ipd.admission-form', compact('patients', 'doctors', 'availableBeds', 'wards'));
    }

    // ─── Store ───────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id'             => ['required', 'integer', 'exists:patients,id'],
            'bed_id'                 => ['required', 'integer', 'exists:beds,id'],
            'primary_doctor_id'      => ['required', 'integer', 'exists:users,id'],
            'admission_type'         => ['required', 'string', 'max:50'],
            'diagnosis_at_admission' => ['required', 'string', 'max:1000'],
            'diet_type'              => ['nullable', 'string', 'max:100'],
            'estimated_days'         => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $clinicId = auth()->user()->clinic_id;

        $bed = Bed::where('id', $validated['bed_id'])
            ->where('clinic_id', $clinicId)
            ->where('status', 'available')
            ->firstOrFail();

        // Generate admission number: IPD + YYYYMMDD + 4-digit sequence
        $todayPrefix = 'IPD' . date('Ymd');
        $lastToday   = IpdAdmission::where('admission_number', 'like', "{$todayPrefix}%")
            ->max('admission_number');

        if ($lastToday) {
            $lastSeq = (int) substr($lastToday, -4);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        $admissionNumber = $todayPrefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        $admission = IpdAdmission::create([
            'clinic_id'              => $clinicId,
            'patient_id'             => $validated['patient_id'],
            'bed_id'                 => $bed->id,
            'ward_id'                => $bed->ward_id,
            'primary_doctor_id'      => $validated['primary_doctor_id'],
            'admitted_by'            => auth()->id(),
            'admission_number'       => $admissionNumber,
            'admission_type'         => $validated['admission_type'],
            'diagnosis_at_admission' => $validated['diagnosis_at_admission'],
            'diet_type'              => $validated['diet_type'] ?? null,
            'estimated_days'         => $validated['estimated_days'] ?? null,
            'admission_date'         => now(),
            'status'                 => 'admitted',
        ]);

        $bed->update(['status' => 'occupied']);

        Log::info('IPD admission created via web', [
            'admission_id'     => $admission->id,
            'admission_number' => $admission->admission_number,
            'patient_id'       => $admission->patient_id,
            'bed_id'           => $bed->id,
            'clinic_id'        => $clinicId,
            'admitted_by'      => auth()->id(),
        ]);

        return redirect()
            ->route('ipd.show', $admission)
            ->with('success', "Admission {$admissionNumber} created successfully.");
    }

    // ─── Show ────────────────────────────────────────────────────────────────

    public function show(IpdAdmission $admission): View
    {
        $this->authorizeClinic($admission->clinic_id);

        $admission->load([
            'patient',
            'bed.ward',
            'ward',
            'primaryDoctor',
            'admittedBy',
        ]);

        $vitals = $admission->vitals()
            ->with('recordedBy')
            ->latest('recorded_at')
            ->limit(20)
            ->get();

        $progressNotes = $admission->progressNotes()
            ->with('author')
            ->orderByDesc('note_date')
            ->get();

        $medicationOrders = $admission->medicationOrders()
            ->with('orderedBy')
            ->get();

        return view('ipd.show', compact('admission', 'vitals', 'progressNotes', 'medicationOrders'));
    }

    // ─── Discharge ───────────────────────────────────────────────────────────

    public function discharge(Request $request, IpdAdmission $admission): RedirectResponse
    {
        $this->authorizeClinic($admission->clinic_id);

        $validated = $request->validate([
            'discharge_type'   => ['required', 'string', 'max:100'],
            'final_diagnosis'  => ['required', 'string', 'max:2000'],
            'discharge_notes'  => ['nullable', 'string'],
        ]);

        $admission->update([
            'discharge_date'   => now(),
            'discharge_type'   => $validated['discharge_type'],
            'final_diagnosis'  => $validated['final_diagnosis'],
            'discharge_notes'  => $validated['discharge_notes'] ?? null,
            'status'           => 'discharged',
        ]);

        $admission->bed->update(['status' => 'cleaning']);

        AuditLog::log(
            'discharged',
            "Patient {$admission->patient->name} discharged ({$validated['discharge_type']})",
            IpdAdmission::class,
            $admission->id,
            ['status' => 'admitted'],
            ['status' => 'discharged', 'discharge_type' => $validated['discharge_type']]
        );

        Log::info('Patient discharged', [
            'admission_id'     => $admission->id,
            'admission_number' => $admission->admission_number,
            'discharge_type'   => $validated['discharge_type'],
            'discharged_by'    => auth()->id(),
        ]);

        return redirect()
            ->route('ipd.index')
            ->with('success', "Patient {$admission->patient->name} discharged successfully.");
    }

    // ─── Record Vitals ───────────────────────────────────────────────────────

    public function recordVitals(Request $request, IpdAdmission $admission): JsonResponse
    {
        $this->authorizeClinic($admission->clinic_id);

        $validated = $request->validate([
            'temperature'      => ['nullable', 'numeric', 'min:30', 'max:45'],
            'pulse'            => ['nullable', 'integer', 'min:20', 'max:300'],
            'bp_systolic'      => ['nullable', 'integer', 'min:50', 'max:250'],
            'bp_diastolic'     => ['nullable', 'integer', 'min:30', 'max:150'],
            'respiratory_rate' => ['nullable', 'integer', 'min:4', 'max:60'],
            'spo2'             => ['nullable', 'numeric', 'min:50', 'max:100'],
            'pain_score'       => ['nullable', 'integer', 'min:0', 'max:10'],
            'gcs'              => ['nullable', 'integer', 'min:3', 'max:15'],
            'weight'           => ['nullable', 'numeric', 'min:1', 'max:500'],
            'height'           => ['nullable', 'numeric', 'min:30', 'max:250'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $vital = $admission->vitals()->create(array_merge($validated, [
            'recorded_by' => auth()->id(),
            'recorded_at' => now(),
        ]));

        Log::info('IPD vitals recorded', [
            'admission_id' => $admission->id,
            'vital_id'     => $vital->id,
            'recorded_by'  => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vitals recorded successfully.',
            'vital'   => $vital,
        ]);
    }

    // ─── Add Progress Note ───────────────────────────────────────────────────

    public function addProgressNote(Request $request, IpdAdmission $admission): JsonResponse
    {
        $this->authorizeClinic($admission->clinic_id);

        $validated = $request->validate([
            'note_type'   => ['required', 'string', 'max:50'],
            'note_date'   => ['required', 'date'],
            'note_time'   => ['required', 'string'],
            'subjective'  => ['required', 'string'],
            'objective'   => ['required', 'string'],
            'assessment'  => ['required', 'string'],
            'plan'        => ['required', 'string'],
            'notes'       => ['nullable', 'string'],
        ]);

        $note = $admission->progressNotes()->create(array_merge($validated, [
            'ipd_admission_id' => $admission->id,
            'author_id'        => auth()->id(),
        ]));

        Log::info('IPD progress note added', [
            'admission_id' => $admission->id,
            'note_id'      => $note->id,
            'author_id'    => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Progress note added successfully.',
            'note'    => $note->load('author'),
        ]);
    }

    // ─── Print Prescription ─────────────────────────────────────────────────

    public function printPrescription(IpdAdmission $admission): View
    {
        $this->authorizeClinic($admission->clinic_id);

        $admission->load(['patient', 'primaryDoctor', 'ward', 'bed']);

        $medicationOrders = IpdMedicationOrder::where('ipd_admission_id', $admission->id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at')
            ->get();

        return view('ipd.print-prescription', compact('admission', 'medicationOrders'));
    }

    // ─── Visiting Card / Admission Slip ──────────────────────────────────────

    public function printCard(IpdAdmission $admission): View
    {
        $admission->load(['patient', 'bed.room.ward', 'primaryDoctor', 'ward']);
        return view('ipd.visiting-card', compact('admission'));
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function authorizeClinic(int $resourceClinicId): void
    {
        abort_unless(auth()->user()->clinic_id === $resourceClinicId, 403);
    }
}
