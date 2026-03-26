<?php

namespace App\Http\Controllers\Patients;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\CreatePatientRequest;
use App\Models\Patient;
use App\Services\S3Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function __construct(
        private readonly S3Service $s3,
    ) {}

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * GET /patients
     * Paginated list with search by name/ABHA/phone and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $query = Patient::where('clinic_id', $clinicId)
            ->whereNull('deleted_at');

        // Full-text style search across name, phone, abha_id
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%")
                  ->orWhere('abha_id', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($request->filled('doctor_id')) {
            // Filter patients who have had visits with this doctor
            $query->whereHas('visits', fn($q) => $q->where('doctor_id', $request->integer('doctor_id')));
        }

        if ($request->filled('specialty')) {
            $query->whereHas('visits', fn($q) => $q->where('specialty', $request->input('specialty')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->boolean('flagged')) {
            $query->where('is_flagged', true);
        }

        $patients = $query
            ->select(['id', 'name', 'phone', 'email', 'dob', 'gender', 'blood_group', 'abha_id', 'is_flagged', 'photo_url', 'created_at'])
            ->withCount('visits')
            ->orderBy($request->input('sort_by', 'name'), $request->input('sort_dir', 'asc'))
            ->paginate($request->integer('per_page', 25));

        return response()->json([
            'data'    => $patients->items(),
            'message' => 'Patients retrieved',
            'meta'    => [
                'total'        => $patients->total(),
                'per_page'     => $patients->perPage(),
                'current_page' => $patients->currentPage(),
                'last_page'    => $patients->lastPage(),
            ],
        ]);
    }

    /**
     * GET /patients/{id}
     * Full patient profile: demographics, ABHA, allergies, family history,
     * active medications, visit count.
     */
    public function show(int $id): JsonResponse
    {
        $patient = Patient::with([
            'activeInsurance',
            'emergencyContact',
        ])
        ->withCount('visits')
        ->where('clinic_id', auth()->user()->clinic_id)
        ->findOrFail($id);

        // Active medications from latest visit prescriptions
        $activeMeds = DB::table('prescriptions')
            ->join('visits', 'prescriptions.visit_id', '=', 'visits.id')
            ->join('prescription_drugs', 'prescription_drugs.prescription_id', '=', 'prescriptions.id')
            ->where('visits.patient_id', $id)
            ->where('prescriptions.is_active', true)
            ->select('prescription_drugs.*', 'prescriptions.prescribed_at')
            ->orderByDesc('prescriptions.prescribed_at')
            ->get();

        return response()->json([
            'data'    => array_merge($patient->toArray(), [
                'active_medications' => $activeMeds,
            ]),
            'message' => 'Patient profile retrieved',
            'meta'    => [],
        ]);
    }

    /**
     * POST /patients
     * Register a new patient. Optionally initiate ABHA creation.
     */
    public function create(CreatePatientRequest $request): JsonResponse
    {
        $clinicId  = auth()->user()->clinic_id;
        $validated = $request->validated();

        $patient = DB::transaction(function () use ($validated, $clinicId) {
            $patient = Patient::create([
                'clinic_id'          => $clinicId,
                'name'               => $validated['name'],
                'phone'              => $validated['phone'],
                'dob'                => $validated['dob'],
                'gender'             => $validated['gender'],
                'blood_group'        => $validated['blood_group'] ?? null,
                'email'              => $validated['email'] ?? null,
                'address_line1'      => $validated['address_line1'] ?? null,
                'address_line2'      => $validated['address_line2'] ?? null,
                'city'               => $validated['city'] ?? null,
                'state'              => $validated['state'] ?? null,
                'pincode'            => $validated['pincode'] ?? null,
                'allergies'          => $validated['allergies'] ?? [],
                'family_history'     => $validated['family_history'] ?? null,
                'registered_by'      => auth()->id(),
                'status'             => 'active',
            ]);

            // Persist emergency contact if provided
            if (! empty($validated['emergency_contact_name'])) {
                $patient->emergencyContact()->create([
                    'name'         => $validated['emergency_contact_name'],
                    'phone'        => $validated['emergency_contact_phone'],
                    'relationship' => $validated['emergency_contact_relationship'] ?? null,
                ]);
            }

            return $patient;
        });

        // Initiate ABHA creation asynchronously if requested
        $abhaInitiated = false;
        if ($request->boolean('initiate_abha') && $patient->phone) {
            try {
                // Dispatch ABDM job — implementation in ABDMController pipeline
                dispatch(new \App\Jobs\InitiateAbhaCreation($patient));
                $abhaInitiated = true;
            } catch (\Throwable) {
                // Non-critical; patient record is still created
            }
        }

        return response()->json([
            'data'    => $patient->load('emergencyContact'),
            'message' => 'Patient registered successfully',
            'meta'    => ['abha_initiated' => $abhaInitiated],
        ], 201);
    }

    /**
     * PUT /patients/{id}
     * Update demographics, allergies, or insurance details.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $patient = Patient::where('clinic_id', auth()->user()->clinic_id)->findOrFail($id);

        $validated = $request->validate([
            'name'                         => 'sometimes|string|max:100',
            'phone'                        => "sometimes|digits:10|unique:patients,phone,{$id},id",
            'email'                        => 'nullable|email',
            'dob'                          => 'sometimes|date|before:today',
            'gender'                       => 'sometimes|in:male,female,other',
            'blood_group'                  => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
            'address_line1'                => 'nullable|string|max:255',
            'address_line2'                => 'nullable|string|max:255',
            'city'                         => 'nullable|string|max:100',
            'state'                        => 'nullable|string|max:100',
            'pincode'                      => 'nullable|digits:6',
            'allergies'                    => 'nullable|array',
            'allergies.*'                  => 'string|max:100',
            'family_history'               => 'nullable|string|max:2000',
            'insurance_provider'           => 'nullable|string|max:100',
            'insurance_policy_number'      => 'nullable|string|max:100',
            'insurance_expiry'             => 'nullable|date',
            'emergency_contact_name'       => 'nullable|string|max:100',
            'emergency_contact_phone'      => 'nullable|digits:10',
            'emergency_contact_relationship' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($patient, $validated) {
            $patient->fill($validated)->save();

            if (isset($validated['emergency_contact_name'])) {
                $patient->emergencyContact()->updateOrCreate(
                    ['patient_id' => $patient->id],
                    [
                        'name'         => $validated['emergency_contact_name'],
                        'phone'        => $validated['emergency_contact_phone'] ?? null,
                        'relationship' => $validated['emergency_contact_relationship'] ?? null,
                    ]
                );
            }
        });

        return response()->json([
            'data'    => $patient->fresh('emergencyContact'),
            'message' => 'Patient updated',
            'meta'    => [],
        ]);
    }

    /**
     * DELETE /patients/{id}
     * Soft-delete patient record.
     */
    public function delete(int $id): JsonResponse
    {
        $patient = Patient::where('clinic_id', auth()->user()->clinic_id)->findOrFail($id);
        $patient->delete(); // relies on SoftDeletes trait on the model

        return response()->json([
            'data'    => null,
            'message' => 'Patient record deleted',
            'meta'    => [],
        ]);
    }

    // -------------------------------------------------------------------------
    // Timeline & Clinical Data
    // -------------------------------------------------------------------------

    /**
     * GET /patients/{id}/timeline
     * Full medical timeline: visits, invoices, photos, prescriptions, lab results.
     * Filterable by type, paginated.
     */
    public function timeline(Request $request, int $id): JsonResponse
    {
        $patient = Patient::where('clinic_id', auth()->user()->clinic_id)->findOrFail($id);

        $request->validate([
            'type'     => 'nullable|in:visit,invoice,photo,prescription,lab_result',
            'from'     => 'nullable|date',
            'to'       => 'nullable|date',
            'per_page' => 'nullable|integer|max:50',
        ]);

        $allowedTypes = $request->filled('type')
            ? [$request->input('type')]
            : ['visit', 'invoice', 'photo', 'prescription', 'lab_result'];

        $events = collect();

        if (in_array('visit', $allowedTypes)) {
            $visitQuery = DB::table('visits')
                ->where('patient_id', $id)
                ->where('clinic_id', $patient->clinic_id)
                ->select('id', DB::raw("'visit' as type"), 'status', 'specialty', DB::raw('NULL as description'), 'started_at as event_at');

            if ($request->filled('from')) {
                $visitQuery->where('started_at', '>=', $request->input('from'));
            }
            if ($request->filled('to')) {
                $visitQuery->where('started_at', '<=', $request->input('to'));
            }

            $events = $events->concat($visitQuery->get());
        }

        if (in_array('invoice', $allowedTypes)) {
            $invoiceQuery = DB::table('invoices')
                ->where('patient_id', $id)
                ->where('clinic_id', $patient->clinic_id)
                ->select('id', DB::raw("'invoice' as type"), 'status', DB::raw('NULL as specialty'), 'invoice_number as description', 'created_at as event_at');

            if ($request->filled('from')) {
                $invoiceQuery->where('created_at', '>=', $request->input('from'));
            }
            if ($request->filled('to')) {
                $invoiceQuery->where('created_at', '<=', $request->input('to'));
            }

            $events = $events->concat($invoiceQuery->get());
        }

        if (in_array('photo', $allowedTypes)) {
            $photoQuery = DB::table('patient_photos')
                ->where('patient_id', $id)
                ->where('clinic_id', $patient->clinic_id)
                ->select('id', DB::raw("'photo' as type"), DB::raw('NULL as status'), DB::raw('NULL as specialty'), 's3_key as description', 'created_at as event_at');

            $events = $events->concat($photoQuery->get());
        }

        if (in_array('prescription', $allowedTypes)) {
            $rxQuery = DB::table('prescriptions')
                ->join('visits', 'prescriptions.visit_id', '=', 'visits.id')
                ->where('visits.patient_id', $id)
                ->where('visits.clinic_id', $patient->clinic_id)
                ->select('prescriptions.id', DB::raw("'prescription' as type"), DB::raw('NULL as status'), 'visits.specialty', DB::raw('NULL as description'), 'prescriptions.prescribed_at as event_at');

            $events = $events->concat($rxQuery->get());
        }

        if (in_array('lab_result', $allowedTypes)) {
            $labQuery = DB::table('lab_results')
                ->where('patient_id', $id)
                ->where('clinic_id', $patient->clinic_id)
                ->select('id', DB::raw("'lab_result' as type"), 'status', DB::raw('NULL as specialty'), 'test_name as description', 'resulted_at as event_at');

            $events = $events->concat($labQuery->get());
        }

        // Sort all events desc by event_at and paginate in-memory
        $perPage  = $request->integer('per_page', 20);
        $page     = $request->integer('page', 1);
        $sorted   = $events->sortByDesc('event_at')->values();
        $paginated = $sorted->forPage($page, $perPage);

        return response()->json([
            'data'    => $paginated->values(),
            'message' => 'Timeline retrieved',
            'meta'    => [
                'total'        => $sorted->count(),
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($sorted->count() / $perPage),
            ],
        ]);
    }

    /**
     * GET /patients/{id}/vitals
     * Historical vitals chart data: weight, BP, SpO2 over time.
     */
    public function vitals(Request $request, int $id): JsonResponse
    {
        Patient::where('clinic_id', auth()->user()->clinic_id)->findOrFail($id);

        $from = $request->input('from', now()->subMonths(6)->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $vitals = DB::table('vitals')
            ->where('patient_id', $id)
            ->whereBetween('recorded_at', [$from, $to])
            ->orderBy('recorded_at')
            ->select([
                'recorded_at',
                'weight_kg',
                'height_cm',
                'bp_systolic',
                'bp_diastolic',
                'spo2',
                'pulse_bpm',
                'temperature_c',
                'respiratory_rate',
            ])
            ->get();

        // Structure into chart-friendly series
        $series = [
            'weight'      => $vitals->whereNotNull('weight_kg')->map(fn($v)   => ['x' => $v->recorded_at, 'y' => $v->weight_kg])->values(),
            'bp'          => $vitals->whereNotNull('bp_systolic')->map(fn($v) => ['x' => $v->recorded_at, 'systolic' => $v->bp_systolic, 'diastolic' => $v->bp_diastolic])->values(),
            'spo2'        => $vitals->whereNotNull('spo2')->map(fn($v)        => ['x' => $v->recorded_at, 'y' => $v->spo2])->values(),
            'pulse'       => $vitals->whereNotNull('pulse_bpm')->map(fn($v)   => ['x' => $v->recorded_at, 'y' => $v->pulse_bpm])->values(),
            'temperature' => $vitals->whereNotNull('temperature_c')->map(fn($v) => ['x' => $v->recorded_at, 'y' => $v->temperature_c])->values(),
        ];

        return response()->json([
            'data'    => $series,
            'message' => 'Vitals retrieved',
            'meta'    => ['from' => $from, 'to' => $to, 'data_points' => $vitals->count()],
        ]);
    }

    // -------------------------------------------------------------------------
    // Media & Search
    // -------------------------------------------------------------------------

    /**
     * POST /patients/{id}/photo
     * Upload patient photo to S3; validate file type and size.
     */
    public function uploadPhoto(Request $request, int $id): JsonResponse
    {
        $patient = Patient::where('clinic_id', auth()->user()->clinic_id)->findOrFail($id);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('photo');

        $s3Key = "clinics/{$patient->clinic_id}/patients/{$id}/photos/" . Str::uuid() . '.' . $file->getClientOriginalExtension();

        $url = $this->s3->upload($file, $s3Key);

        DB::transaction(function () use ($patient, $s3Key, $url, $id) {
            DB::table('patient_photos')->insert([
                'patient_id'  => $id,
                'clinic_id'   => $patient->clinic_id,
                's3_key'      => $s3Key,
                'url'         => $url,
                'uploaded_by' => auth()->id(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Update patient's primary photo_url to the latest upload
            $patient->update(['photo_url' => $url]);
        });

        return response()->json([
            'data'    => ['url' => $url, 's3_key' => $s3Key],
            'message' => 'Photo uploaded',
            'meta'    => [],
        ], 201);
    }

    /**
     * GET /patients/drugs/search
     * FULLTEXT search of indian_drugs table for drug autocomplete.
     */
    public function searchDrug(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $term = $request->input('q');

        $drugs = DB::table('indian_drugs')
            ->whereRaw('MATCH(drug_name, brand_name, generic_name) AGAINST(? IN BOOLEAN MODE)', ["{$term}*"])
            ->select([
                'id',
                'drug_name',
                'brand_name',
                'generic_name',
                'strength',
                'form',           // tablet, capsule, syrup, injection, cream…
                'manufacturer',
                'schedule',       // H, H1, X, OTC
            ])
            ->limit(20)
            ->get();

        // Fallback to LIKE if FULLTEXT returns nothing (non-indexed tables / dev env)
        if ($drugs->isEmpty()) {
            $drugs = DB::table('indian_drugs')
                ->where('drug_name', 'like', "%{$term}%")
                ->orWhere('brand_name', 'like', "%{$term}%")
                ->orWhere('generic_name', 'like', "%{$term}%")
                ->select(['id', 'drug_name', 'brand_name', 'generic_name', 'strength', 'form', 'manufacturer', 'schedule'])
                ->limit(20)
                ->get();
        }

        return response()->json([
            'data'    => $drugs,
            'message' => 'Drug search results',
            'meta'    => ['query' => $term, 'count' => $drugs->count()],
        ]);
    }

    // -------------------------------------------------------------------------
    // Flags, Merge, Export
    // -------------------------------------------------------------------------

    /**
     * POST /patients/{id}/flag
     * Toggle the patient's follow-up flag.
     */
    public function flagPatient(Request $request, int $id): JsonResponse
    {
        $patient = Patient::where('clinic_id', auth()->user()->clinic_id)->findOrFail($id);

        $patient->is_flagged       = ! $patient->is_flagged;
        $patient->flag_reason      = $request->input('reason');
        $patient->flagged_by       = $patient->is_flagged ? auth()->id() : null;
        $patient->flagged_at       = $patient->is_flagged ? now() : null;
        $patient->save();

        return response()->json([
            'data'    => ['is_flagged' => $patient->is_flagged],
            'message' => $patient->is_flagged ? 'Patient flagged for follow-up' : 'Patient flag removed',
            'meta'    => [],
        ]);
    }

    /**
     * POST /patients/merge
     * Merge duplicate patient records: transfer all visits/invoices to primary,
     * then soft-delete the duplicate.
     */
    public function mergePatients(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'primary_id'   => 'required|integer|exists:patients,id',
            'duplicate_id' => 'required|integer|exists:patients,id|different:primary_id',
            'reason'       => 'nullable|string|max:500',
        ]);

        $clinicId = auth()->user()->clinic_id;

        $primary   = Patient::where('clinic_id', $clinicId)->findOrFail($validated['primary_id']);
        $duplicate = Patient::where('clinic_id', $clinicId)->findOrFail($validated['duplicate_id']);

        DB::transaction(function () use ($primary, $duplicate, $validated) {
            $tables = [
                'visits'        => 'patient_id',
                'appointments'  => 'patient_id',
                'invoices'      => 'patient_id',
                'prescriptions' => 'patient_id',
                'lab_results'   => 'patient_id',
                'patient_photos' => 'patient_id',
                'vitals'        => 'patient_id',
            ];

            foreach ($tables as $table => $column) {
                DB::table($table)
                    ->where($column, $duplicate->id)
                    ->update([$column => $primary->id]);
            }

            // Merge allergy arrays (union, deduplicate)
            $mergedAllergies = collect(array_merge(
                $primary->allergies ?? [],
                $duplicate->allergies ?? []
            ))->unique()->values()->all();

            $primary->allergies = $mergedAllergies;
            $primary->save();

            // Log merge audit
            DB::table('patient_merge_logs')->insert([
                'primary_patient_id'   => $primary->id,
                'duplicate_patient_id' => $duplicate->id,
                'merged_by'            => auth()->id(),
                'reason'               => $validated['reason'] ?? null,
                'merged_at'            => now(),
            ]);

            // Soft-delete the duplicate
            $duplicate->delete();
        });

        return response()->json([
            'data'    => $primary->fresh(),
            'message' => "Patient #{$duplicate->id} merged into #{$primary->id} and deleted",
            'meta'    => [],
        ]);
    }

    /**
     * GET /patients/{id}/export-pdf
     * Generate a patient summary PDF (returns base64-encoded stub for renderer).
     */
    public function exportPdf(int $id): JsonResponse
    {
        $patient = Patient::with([
            'emergencyContact',
            'activeInsurance',
        ])
        ->withCount('visits')
        ->where('clinic_id', auth()->user()->clinic_id)
        ->findOrFail($id);

        // Collect recent visits
        $recentVisits = DB::table('visits')
            ->where('patient_id', $id)
            ->orderByDesc('started_at')
            ->limit(10)
            ->get(['id', 'specialty', 'diagnosis', 'status', 'started_at', 'finalised_at']);

        // Collect active medications
        $activeMeds = DB::table('prescriptions')
            ->join('visits', 'prescriptions.visit_id', '=', 'visits.id')
            ->join('prescription_drugs', 'prescription_drugs.prescription_id', '=', 'prescriptions.id')
            ->where('visits.patient_id', $id)
            ->where('prescriptions.is_active', true)
            ->select('prescription_drugs.drug_name', 'prescription_drugs.dosage', 'prescription_drugs.frequency')
            ->get();

        // Build PDF data payload — actual rendering delegated to a PDF microservice or Dompdf job
        $pdfData = [
            'generated_at'     => now()->toIso8601String(),
            'clinic_id'        => auth()->user()->clinic_id,
            'patient'          => $patient,
            'recent_visits'    => $recentVisits,
            'active_medications' => $activeMeds,
        ];

        // Base64-encode JSON stub — real implementation would render via Dompdf/Browsershot
        $base64Stub = base64_encode(json_encode($pdfData, JSON_PRETTY_PRINT));

        return response()->json([
            'data'    => [
                'pdf_base64' => $base64Stub,
                'filename'   => "patient-{$id}-summary-" . now()->format('Ymd') . '.pdf',
            ],
            'message' => 'Patient summary PDF generated',
            'meta'    => ['patient_id' => $id],
        ]);
    }
}
