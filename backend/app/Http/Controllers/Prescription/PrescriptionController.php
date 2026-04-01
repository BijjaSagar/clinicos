<?php

namespace App\Http\Controllers\Prescription;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\IndianDrug;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionDrug;
use App\Models\Visit;
use App\Services\DrugInteractionService;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PrescriptionController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // WEB — create form
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Show the prescription creation form (web).
     */
    public function create(Request $request, int $patientId, int $visitId)
    {
        $clinicId = $request->user()->clinic_id;

        $patient = Patient::where('clinic_id', $clinicId)->findOrFail($patientId);
        $visit   = Visit::where('clinic_id', $clinicId)->findOrFail($visitId);
        $clinic  = Clinic::findOrFail($clinicId);

        // Pre-load prescription templates (grouped by diagnosis)
        $templates = $this->prescriptionTemplates();

        // Drug interaction data for front-end
        $interactions = DrugInteractionService::allInteractions();

        return view('prescriptions.create', compact(
            'patient', 'visit', 'clinic', 'templates', 'interactions'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API — CRUD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * List prescriptions (API).
     */
    public function index(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching prescriptions', ['clinic_id' => $clinicId]);

        $query = Prescription::forClinic($clinicId)
            ->with(['patient', 'doctor', 'drugs']);

        if ($request->patient_id) {
            $query->forPatient($request->patient_id);
        }
        if ($request->visit_id) {
            $query->where('visit_id', $request->visit_id);
        }

        $prescriptions = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($prescriptions);
    }

    /**
     * Create prescription + drugs, optionally generate PDF.
     */
    public function store(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Creating prescription', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'visit_id'              => 'required|exists:visits,id',
            'patient_id'            => 'required|exists:patients,id',
            'valid_days'            => 'nullable|integer|min:1|max:365',
            'notes'                 => 'nullable|string|max:2000',
            'drugs'                 => 'required|array|min:1',
            'drugs.*.drug_name'     => 'required|string|max:200',
            'drugs.*.generic_name'  => 'nullable|string|max:200',
            'drugs.*.strength'      => 'nullable|string|max:50',
            'drugs.*.form'          => 'nullable|string|max:50',
            'drugs.*.dose'          => 'required|string|max:100',
            'drugs.*.frequency'     => 'required|string|max:100',
            'drugs.*.route'         => 'nullable|string|max:30',
            'drugs.*.duration'      => 'nullable|string|max:50',
            'drugs.*.quantity'      => 'nullable|integer|min:0',
            'drugs.*.instructions'  => 'nullable|string',
            'drugs.*.drug_db_id'    => 'nullable|exists:indian_drugs,id',
        ]);

        DB::beginTransaction();
        try {
            $prescription = Prescription::create([
                'clinic_id'  => $clinicId,
                'visit_id'   => $validated['visit_id'],
                'patient_id' => $validated['patient_id'],
                'doctor_id'  => $request->user()->id,
                'valid_days'  => $validated['valid_days'] ?? 30,
            ]);

            foreach ($validated['drugs'] as $index => $drugData) {
                $prescription->drugs()->create([
                    'drug_db_id'    => $drugData['drug_db_id'] ?? null,
                    'drug_name'     => $drugData['drug_name'],
                    'generic_name'  => $drugData['generic_name'] ?? null,
                    'strength'      => $drugData['strength'] ?? null,
                    'form'          => $drugData['form'] ?? null,
                    'dose'          => $drugData['dose'],
                    'frequency'     => $drugData['frequency'],
                    'route'         => $drugData['route'] ?? 'oral',
                    'duration'      => $drugData['duration'] ?? null,
                    'instructions'  => $drugData['instructions'] ?? null,
                    'sort_order'    => $index,
                ]);
            }

            // Auto-generate PDF
            $pdfUrl = $this->generatePdfFile($prescription);
            if ($pdfUrl) {
                $prescription->update(['pdf_url' => $pdfUrl]);
            }

            DB::commit();

            Log::info('Prescription created', [
                'prescription_id' => $prescription->id,
                'drug_count'      => count($validated['drugs']),
            ]);

            return response()->json([
                'message'      => 'Prescription created successfully',
                'prescription' => $prescription->load(['drugs', 'patient', 'doctor']),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Prescription creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Show a single prescription (API).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;

        $prescription = Prescription::forClinic($clinicId)
            ->with(['drugs', 'patient', 'doctor', 'visit', 'clinic'])
            ->findOrFail($id);

        return response()->json(['prescription' => $prescription]);
    }

    /**
     * Generate & stream PDF for download.
     */
    public function pdf(Request $request, int $id)
    {
        $clinicId = $request->user()->clinic_id;

        $prescription = Prescription::forClinic($clinicId)
            ->with(['drugs', 'patient', 'doctor', 'clinic'])
            ->findOrFail($id);

        $clinic  = $prescription->clinic;
        $patient = $prescription->patient;
        $doctor  = $prescription->doctor;
        $drugs   = $prescription->drugs;

        $pdf = Pdf::loadView('prescriptions.pdf', compact(
            'prescription', 'clinic', 'patient', 'doctor', 'drugs'
        ));

        $pdf->setPaper('a4', 'portrait');

        $filename = 'prescription_' . $prescription->id . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Generate PDF, upload to storage, send link via WhatsApp.
     */
    public function sendWhatsApp(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;

        $prescription = Prescription::forClinic($clinicId)
            ->with(['drugs', 'patient', 'doctor', 'clinic'])
            ->findOrFail($id);

        $patient = $prescription->patient;
        if (!$patient?->phone) {
            return response()->json([
                'message' => 'Patient does not have a phone number on record.',
            ], 422);
        }

        try {
            // Generate and store PDF
            $pdfUrl = $this->generatePdfFile($prescription);

            if (!$pdfUrl) {
                return response()->json(['message' => 'Failed to generate PDF'], 500);
            }

            // Send via WhatsApp
            $whatsApp = app(WhatsAppService::class);
            $response = $whatsApp->sendPrescription($patient, $prescription, $pdfUrl);

            // Mark as sent
            $prescription->update([
                'whatsapp_sent_at'   => now(),
                'whatsapp_message_id' => $response['messages'][0]['id'] ?? null,
                'pdf_url'            => $pdfUrl,
            ]);

            Log::info('Prescription sent via WhatsApp', [
                'prescription_id' => $id,
                'patient_id'      => $patient->id,
            ]);

            return response()->json([
                'message' => 'Prescription sent via WhatsApp successfully',
                'pdf_url' => $pdfUrl,
            ]);

        } catch (\Throwable $e) {
            Log::error('Prescription WhatsApp send failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to send prescription: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send prescription via WhatsApp (alias for API route).
     */
    public function send(Request $request, int $id): JsonResponse
    {
        return $this->sendWhatsApp($request, $id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Templates & drug search
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return JSON of template drugs for a given diagnosis.
     */
    public function loadTemplate(Request $request, string $diagnosis): JsonResponse
    {
        $templates = $this->prescriptionTemplates();

        $key = strtolower(str_replace(['-', '_'], ' ', $diagnosis));

        $matched = collect($templates)->first(function (array $tpl) use ($key) {
            return strtolower($tpl['diagnosis']) === $key;
        });

        if (!$matched) {
            return response()->json(['message' => 'Template not found', 'drugs' => []], 404);
        }

        return response()->json([
            'diagnosis' => $matched['diagnosis'],
            'drugs'     => $matched['drugs'],
        ]);
    }

    /**
     * AJAX drug search — returns top 10 matching drugs.
     */
    public function drugSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $search = $validated['q'];

        $drugs = IndianDrug::where(function ($q) use ($search) {
                $q->where('generic_name', 'like', "%{$search}%")
                  ->orWhere('brand_names', 'like', "%{$search}%")
                  ->orWhere('drug_class', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();

        return response()->json(['drugs' => $drugs]);
    }

    /**
     * Check drug interactions via API.
     */
    public function checkInteractions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'drugs'   => 'required|array|min:2',
            'drugs.*' => 'required|string|max:200',
        ]);

        $conflicts = DrugInteractionService::check($validated['drugs']);

        return response()->json(['interactions' => $conflicts]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate PDF file and store it; return public URL.
     */
    private function generatePdfFile(Prescription $prescription): ?string
    {
        try {
            $prescription->loadMissing(['drugs', 'patient', 'doctor', 'clinic']);

            $clinic  = $prescription->clinic;
            $patient = $prescription->patient;
            $doctor  = $prescription->doctor;
            $drugs   = $prescription->drugs;

            $pdf = Pdf::loadView('prescriptions.pdf', compact(
                'prescription', 'clinic', 'patient', 'doctor', 'drugs'
            ));
            $pdf->setPaper('a4', 'portrait');

            $path = "prescriptions/{$prescription->clinic_id}/{$prescription->id}_" . now()->format('Ymd_His') . '.pdf';
            Storage::disk('public')->put($path, $pdf->output());

            return Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            Log::error('PDF generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Built-in prescription templates by diagnosis.
     */
    private function prescriptionTemplates(): array
    {
        return [
            [
                'diagnosis' => 'Acne Vulgaris',
                'drugs' => [
                    ['drug_name' => 'Adapalene Gel 0.1%', 'generic_name' => 'Adapalene', 'dose' => 'Pea-sized', 'frequency' => 'HS', 'route' => 'topical', 'duration' => '12 Weeks', 'instructions' => 'Apply at night on dry face, avoid eyes and lips'],
                    ['drug_name' => 'Clindamycin Gel 1%', 'generic_name' => 'Clindamycin', 'dose' => 'Thin layer', 'frequency' => 'BD', 'route' => 'topical', 'duration' => '8 Weeks', 'instructions' => 'Apply morning and evening on affected area'],
                    ['drug_name' => 'Doxycycline 100mg', 'generic_name' => 'Doxycycline', 'dose' => '100mg', 'frequency' => 'BD', 'route' => 'oral', 'duration' => '6 Weeks', 'instructions' => 'After food with plenty of water, avoid sun exposure'],
                    ['drug_name' => 'Benzoyl Peroxide Gel 2.5%', 'generic_name' => 'Benzoyl Peroxide', 'dose' => 'Thin layer', 'frequency' => 'OD', 'route' => 'topical', 'duration' => '8 Weeks', 'instructions' => 'Apply on alternate nights initially'],
                ],
            ],
            [
                'diagnosis' => 'Eczema',
                'drugs' => [
                    ['drug_name' => 'Mometasone Cream 0.1%', 'generic_name' => 'Mometasone Furoate', 'dose' => 'Thin layer', 'frequency' => 'BD', 'route' => 'topical', 'duration' => '2 Weeks', 'instructions' => 'Apply on affected areas, avoid face'],
                    ['drug_name' => 'Cetirizine 10mg', 'generic_name' => 'Cetirizine', 'dose' => '10mg', 'frequency' => 'OD', 'route' => 'oral', 'duration' => '2 Weeks', 'instructions' => 'At bedtime'],
                    ['drug_name' => 'Moisturiser (Cetaphil / Physiogel)', 'generic_name' => 'Emollient', 'dose' => 'Liberal', 'frequency' => 'TDS', 'route' => 'topical', 'duration' => 'Ongoing', 'instructions' => 'Apply within 3 minutes of bathing, reapply frequently'],
                ],
            ],
            [
                'diagnosis' => 'Psoriasis',
                'drugs' => [
                    ['drug_name' => 'Clobetasol Propionate Cream 0.05%', 'generic_name' => 'Clobetasol', 'dose' => 'Thin layer', 'frequency' => 'BD', 'route' => 'topical', 'duration' => '2 Weeks', 'instructions' => 'Apply on plaques only, not on face/groin'],
                    ['drug_name' => 'Calcipotriol Ointment', 'generic_name' => 'Calcipotriol', 'dose' => 'Thin layer', 'frequency' => 'BD', 'route' => 'topical', 'duration' => '8 Weeks', 'instructions' => 'Alternate with steroid, max 100g per week'],
                    ['drug_name' => 'Methotrexate 7.5mg', 'generic_name' => 'Methotrexate', 'dose' => '7.5mg', 'frequency' => 'Once weekly', 'route' => 'oral', 'duration' => '12 Weeks', 'instructions' => 'Take once a week ONLY on fixed day, with folic acid next day'],
                    ['drug_name' => 'Folic Acid 5mg', 'generic_name' => 'Folic Acid', 'dose' => '5mg', 'frequency' => 'Once weekly', 'route' => 'oral', 'duration' => '12 Weeks', 'instructions' => 'Take 24 hours after methotrexate'],
                ],
            ],
            [
                'diagnosis' => 'Fungal Infection',
                'drugs' => [
                    ['drug_name' => 'Terbinafine 250mg', 'generic_name' => 'Terbinafine', 'dose' => '250mg', 'frequency' => 'OD', 'route' => 'oral', 'duration' => '4 Weeks', 'instructions' => 'After food'],
                    ['drug_name' => 'Luliconazole Cream 1%', 'generic_name' => 'Luliconazole', 'dose' => 'Thin layer', 'frequency' => 'OD', 'route' => 'topical', 'duration' => '4 Weeks', 'instructions' => 'Apply at night on affected area and 2cm beyond margin'],
                    ['drug_name' => 'Cetirizine 10mg', 'generic_name' => 'Cetirizine', 'dose' => '10mg', 'frequency' => 'OD', 'route' => 'oral', 'duration' => '2 Weeks', 'instructions' => 'For itch relief'],
                ],
            ],
            [
                'diagnosis' => 'URI',
                'drugs' => [
                    ['drug_name' => 'Paracetamol 500mg', 'generic_name' => 'Paracetamol', 'dose' => '500mg', 'frequency' => 'TDS', 'route' => 'oral', 'duration' => '3 Days', 'instructions' => 'After food, SOS for fever'],
                    ['drug_name' => 'Cetirizine 10mg', 'generic_name' => 'Cetirizine', 'dose' => '10mg', 'frequency' => 'OD', 'route' => 'oral', 'duration' => '5 Days', 'instructions' => 'At bedtime'],
                    ['drug_name' => 'Ambroxol + Guaifenesin Syrup', 'generic_name' => 'Ambroxol', 'dose' => '10ml', 'frequency' => 'TDS', 'route' => 'oral', 'duration' => '5 Days', 'instructions' => 'After food'],
                    ['drug_name' => 'Steam Inhalation', 'generic_name' => 'Steam', 'dose' => '10 min', 'frequency' => 'BD', 'route' => 'inhalation', 'duration' => '5 Days', 'instructions' => 'With plain hot water, twice daily'],
                ],
            ],
            [
                'diagnosis' => 'UTI',
                'drugs' => [
                    ['drug_name' => 'Ciprofloxacin 500mg', 'generic_name' => 'Ciprofloxacin', 'dose' => '500mg', 'frequency' => 'BD', 'route' => 'oral', 'duration' => '5 Days', 'instructions' => 'After food, complete full course'],
                    ['drug_name' => 'Pantoprazole 40mg', 'generic_name' => 'Pantoprazole', 'dose' => '40mg', 'frequency' => 'OD', 'route' => 'oral', 'duration' => '5 Days', 'instructions' => 'Before breakfast'],
                    ['drug_name' => 'Cranberry Capsule', 'generic_name' => 'Cranberry Extract', 'dose' => '1 cap', 'frequency' => 'BD', 'route' => 'oral', 'duration' => '2 Weeks', 'instructions' => 'Drink plenty of water'],
                ],
            ],
            [
                'diagnosis' => 'Hypertension',
                'drugs' => [
                    ['drug_name' => 'Amlodipine 5mg', 'generic_name' => 'Amlodipine', 'dose' => '5mg', 'frequency' => 'OD', 'route' => 'oral', 'duration' => '30 Days', 'instructions' => 'Morning, do not stop abruptly'],
                    ['drug_name' => 'Telmisartan 40mg', 'generic_name' => 'Telmisartan', 'dose' => '40mg', 'frequency' => 'OD', 'route' => 'oral', 'duration' => '30 Days', 'instructions' => 'Morning, monitor BP regularly'],
                    ['drug_name' => 'Ecosprin 75mg', 'generic_name' => 'Aspirin', 'dose' => '75mg', 'frequency' => 'OD', 'route' => 'oral', 'duration' => '30 Days', 'instructions' => 'After lunch'],
                ],
            ],
            [
                'diagnosis' => 'Diabetes',
                'drugs' => [
                    ['drug_name' => 'Metformin 500mg', 'generic_name' => 'Metformin', 'dose' => '500mg', 'frequency' => 'BD', 'route' => 'oral', 'duration' => '30 Days', 'instructions' => 'After food, increase to 1000mg after 2 weeks if tolerated'],
                    ['drug_name' => 'Glimepiride 1mg', 'generic_name' => 'Glimepiride', 'dose' => '1mg', 'frequency' => 'OD', 'route' => 'oral', 'duration' => '30 Days', 'instructions' => 'Before breakfast'],
                    ['drug_name' => 'Atorvastatin 10mg', 'generic_name' => 'Atorvastatin', 'dose' => '10mg', 'frequency' => 'OD', 'route' => 'oral', 'duration' => '30 Days', 'instructions' => 'At bedtime'],
                ],
            ],
        ];
    }
}
