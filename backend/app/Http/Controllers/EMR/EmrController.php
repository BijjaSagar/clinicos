<?php

namespace App\Http\Controllers\EMR;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\EmrTemplate;
use App\Services\FhirBuilder;
use App\Services\AbdmService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmrController extends Controller
{
    public function __construct(
        private readonly FhirBuilder $fhirBuilder,
        private readonly AbdmService $abdmService,
    ) {}

    /**
     * GET /emr/visits/{visitId}
     * Returns full structured visit note with specialty-specific fields.
     */
    public function show(int $visitId): JsonResponse
    {
        $visit = Visit::with([
            'patient',
            'doctor',
            'lesions',
            'scales',
            'procedures',
            'prescriptions.drugs',
            'photos',
            'labOrders',
        ])
        ->forClinic(auth()->user()->clinic_id)
        ->findOrFail($visitId);

        return response()->json([
            'visit'    => $visit,
            'template' => EmrTemplate::forSpecialty($visit->specialty)->fields(),
        ]);
    }

    /**
     * POST /emr/visits
     * Creates a new visit draft linked to an appointment.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id'     => 'required|integer',
            'appointment_id' => 'required|integer',
            'specialty'      => 'required|string|in:dermatology,physiotherapy,dental,ophthalmology,orthopaedics,ent,gynaecology',
            'doctor_id'      => 'required|integer',
        ]);

        $visit = Visit::create([
            ...$validated,
            'clinic_id' => auth()->user()->clinic_id,
            'status'    => 'draft',
            'started_at'=> now(),
        ]);

        return response()->json(['visit' => $visit], 201);
    }

    /**
     * PUT /emr/visits/{visitId}
     * Auto-saves structured visit fields. Accepts specialty-specific JSON payload.
     */
    public function update(Request $request, int $visitId): JsonResponse
    {
        $visit = Visit::forClinic(auth()->user()->clinic_id)->findOrFail($visitId);

        // Merge incoming fields into structured_data JSON column
        $visit->structured_data = array_merge(
            $visit->structured_data ?? [],
            $request->input('fields', [])
        );
        $visit->notes       = $request->input('notes', $visit->notes);
        $visit->diagnosis   = $request->input('diagnosis', $visit->diagnosis);
        $visit->plan        = $request->input('plan', $visit->plan);
        $visit->updated_at  = now();
        $visit->save();

        return response()->json(['visit' => $visit]);
    }

    /**
     * POST /emr/visits/{visitId}/finalise
     * Marks visit as complete, generates FHIR R4 record, pushes to ABDM if consented.
     */
    public function finalise(int $visitId): JsonResponse
    {
        $visit = Visit::with(['patient', 'prescriptions.drugs', 'procedures'])
            ->forClinic(auth()->user()->clinic_id)
            ->where('status', 'draft')
            ->findOrFail($visitId);

        $visit->status       = 'finalised';
        $visit->finalised_at = now();
        $visit->save();

        // Build FHIR R4 Composition
        $fhirBundle = $this->fhirBuilder->fromVisit($visit);
        $visit->fhir_bundle = $fhirBundle;
        $visit->save();

        // Push to ABDM if patient has ABHA and has consented
        if ($visit->patient->abha_id && $visit->patient->abdm_consent_active) {
            $this->abdmService->pushHealthRecord($visit->patient->abha_id, $fhirBundle);
        }

        return response()->json([
            'visit'       => $visit,
            'fhir_pushed' => $visit->patient->abdm_consent_active,
        ]);
    }

    /**
     * GET /emr/templates/{specialty}
     * Returns the field schema for a specialty template.
     */
    public function template(string $specialty): JsonResponse
    {
        $template = EmrTemplate::forSpecialty($specialty)->firstOrFail();
        return response()->json(['template' => $template->fields()]);
    }

    /**
     * POST /emr/visits/{visitId}/lesions
     * Adds a body-map lesion annotation.
     */
    public function addLesion(Request $request, int $visitId): JsonResponse
    {
        $visit = Visit::forClinic(auth()->user()->clinic_id)->findOrFail($visitId);

        $validated = $request->validate([
            'body_region'  => 'required|string',
            'x_pct'        => 'required|numeric|min:0|max:100',
            'y_pct'        => 'required|numeric|min:0|max:100',
            'lesion_type'  => 'required|string', // macule, papule, plaque, vesicle…
            'size_cm'      => 'nullable|numeric',
            'colour'       => 'nullable|string',
            'border'       => 'nullable|string',
            'surface'      => 'nullable|string',
            'notes'        => 'nullable|string',
        ]);

        $lesion = $visit->lesions()->create($validated);

        return response()->json(['lesion' => $lesion], 201);
    }

    /**
     * POST /emr/visits/{visitId}/scales
     * Saves grading scale scores (PASI, IGA, DLQI, ROM, MMT, VAS, etc.)
     */
    public function saveScales(Request $request, int $visitId): JsonResponse
    {
        $visit = Visit::forClinic(auth()->user()->clinic_id)->findOrFail($visitId);

        $scales = $request->input('scales', []);
        // e.g. [['scale' => 'PASI', 'score' => 8.4, 'components' => {...}], ...]

        foreach ($scales as $scale) {
            $visit->scales()->updateOrCreate(
                ['scale_name' => $scale['scale']],
                ['score' => $scale['score'], 'components' => $scale['components'] ?? null]
            );
        }

        return response()->json(['message' => 'Scales saved', 'count' => count($scales)]);
    }

    /**
     * PUT /emr/dental/{patientId}/tooth/{toothCode}
     * Updates a single tooth record in the FDI dental chart.
     * toothCode: 11-18, 21-28, 31-38, 41-48 (permanent) | 51-55... (primary)
     */
    public function updateTooth(Request $request, int $patientId, string $toothCode): JsonResponse
    {
        $validated = $request->validate([
            'status'          => 'nullable|string', // present, missing, extracted, implant
            'caries'          => 'nullable|string', // none, initial, moderate, advanced
            'restoration'     => 'nullable|string', // none, amalgam, composite, crown, bridge, rct
            'mobility'        => 'nullable|integer|min:0|max:3',
            'pocketing_mm'    => 'nullable|array',   // 6-point probing [MB,B,DB,ML,L,DL]
            'recession_mm'    => 'nullable|numeric',
            'bop'             => 'nullable|boolean',
            'procedure_today' => 'nullable|string',
            'notes'           => 'nullable|string',
        ]);

        $record = \App\Models\DentalTooth::updateOrCreate(
            ['patient_id' => $patientId, 'tooth_code' => $toothCode],
            [...$validated, 'updated_by' => auth()->id(), 'clinic_id' => auth()->user()->clinic_id]
        );

        return response()->json(['tooth' => $record]);
    }
}
