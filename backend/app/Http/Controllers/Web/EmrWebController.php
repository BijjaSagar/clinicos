<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\IndianDrug;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\PatientPhoto;
use App\Models\Prescription;
use App\Models\PrescriptionDrug;
use App\Models\Visit;
use App\Models\VisitLesion;
use App\Models\VisitProcedure;
use App\Models\VisitScale;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EmrWebController extends Controller
{
    private ?WhatsAppService $whatsAppService;

    public function __construct(?WhatsAppService $whatsAppService = null)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function index(): View
    {
        Log::info('EmrWebController@index');
        
        $clinicId = auth()->user()->clinic_id;
        
        // Get recent visits with patient info
        $recentVisits = Visit::with(['patient', 'doctor'])
            ->where('clinic_id', $clinicId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Get in-progress visits (today's consultations - draft status means in progress)
        $inProgressVisits = Visit::with(['patient', 'doctor', 'appointment'])
            ->where('clinic_id', $clinicId)
            ->whereDate('created_at', today())
            ->where('status', 'draft')
            ->orderByDesc('created_at')
            ->get();

        // Get today's appointments that need visits
        $todayAppointments = Appointment::with(['patient'])
            ->where('clinic_id', $clinicId)
            ->whereDate('scheduled_at', today())
            ->whereIn('status', ['checked_in', 'in_consultation', 'confirmed'])
            ->whereDoesntHave('visit', function ($q) {
                $q->whereDate('created_at', today());
            })
            ->orderBy('scheduled_at')
            ->get();

        // Stats
        $stats = [
            'today_visits' => Visit::where('clinic_id', $clinicId)->whereDate('created_at', today())->count(),
            'in_progress' => $inProgressVisits->count(),
            'completed_today' => Visit::where('clinic_id', $clinicId)->whereDate('created_at', today())->where('status', 'finalised')->count(),
            'pending_followups' => Visit::where('clinic_id', $clinicId)
                ->whereNotNull('followup_date')
                ->whereBetween('followup_date', [today(), today()->addDays(7)])
                ->count(),
        ];

        return view('emr.index', compact('recentVisits', 'inProgressVisits', 'todayAppointments', 'stats'));
    }

    public function show(Patient $patient, Visit $visit): View
    {
        Log::info('EmrWebController@show', ['patient_id' => $patient->id, 'visit_id' => $visit->id]);
        
        $clinicId = auth()->user()->clinic_id;
        abort_unless($patient->clinic_id === $clinicId && $visit->clinic_id === $clinicId, 403);
        abort_unless($visit->patient_id === $patient->id, 404);

        // Load visit with all relationships
        $visit->load([
            'lesions',
            'scales',
            'procedures',
            'prescriptions.drugs',
            'photos',
            'doctor',
            'invoice.items',
        ]);

        // Load patient with their history
        $patient->load(['photos']);

        // Get patient's visit history
        $visitHistory = Visit::with(['doctor', 'prescriptions'])
            ->where('patient_id', $patient->id)
            ->where('clinic_id', $clinicId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Get current prescription (latest for this visit)
        $prescription = $visit->prescriptions()->with('drugs')->latest()->first();

        // Get patient photos grouped by type
        $patientPhotos = PatientPhoto::where('patient_id', $patient->id)
            ->where('clinic_id', $clinicId)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('photo_type');

        // Get lab orders for this patient
        $labOrders = LabOrder::where('patient_id', $patient->id)
            ->where('clinic_id', $clinicId)
            ->with('tests')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Get previous prescriptions for alerts
        $previousPrescriptions = Prescription::where('patient_id', $patient->id)
            ->where('clinic_id', $clinicId)
            ->where('visit_id', '!=', $visit->id)
            ->with('drugs')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        // Calculate scale changes if applicable
        $previousVisit = Visit::where('patient_id', $patient->id)
            ->where('clinic_id', $clinicId)
            ->where('id', '<', $visit->id)
            ->with('scales')
            ->orderByDesc('created_at')
            ->first();

        $scaleChanges = [];
        if ($previousVisit && $previousVisit->scales->isNotEmpty()) {
            foreach ($visit->scales as $scale) {
                $prevScale = $previousVisit->scales->firstWhere('scale_name', $scale->scale_name);
                if ($prevScale) {
                    $scaleChanges[$scale->scale_name] = [
                        'previous' => $prevScale->score,
                        'current' => $scale->score,
                        'change' => $scale->score - $prevScale->score,
                    ];
                }
            }
        }

        // Available procedures for selection
        $availableProcedures = [
            'Chemical Peel', 'LASER', 'PRP', 'Botox', 'Fillers', 
            'Microneedling', 'Threads', 'Mesotherapy', 'Cryotherapy',
            'Excision', 'Biopsy', 'Cautery', 'RF Treatment'
        ];

        // Common complaints based on specialty
        $commonComplaints = $this->getCommonComplaints(auth()->user()->specialty ?? 'general');

        Log::info('EMR show loaded', [
            'visit_id' => $visit->id,
            'visit_history_count' => $visitHistory->count(),
            'photos_count' => $patientPhotos->flatten()->count(),
        ]);

        return view('emr.show', compact(
            'patient', 'visit', 'visitHistory', 'prescription', 
            'patientPhotos', 'labOrders', 'previousPrescriptions',
            'scaleChanges', 'previousVisit', 'availableProcedures', 'commonComplaints'
        ));
    }

    /**
     * Get common complaints based on specialty
     */
    private function getCommonComplaints(?string $specialty): array
    {
        $complaints = [
            'dermatology' => ['Acne', 'Rash', 'Pigmentation', 'Hair Loss', 'Psoriasis', 'Eczema', 'Itch', 'Warts', 'Fungal Infection'],
            'dental' => ['Tooth Pain', 'Cavity', 'Gum Problem', 'Cleaning', 'Root Canal', 'Extraction', 'Orthodontic'],
            'ophthalmology' => ['Vision Problem', 'Eye Pain', 'Redness', 'Watering', 'Cataract', 'Glaucoma Check'],
            'general' => ['Fever', 'Cold', 'Cough', 'Body Pain', 'Headache', 'Stomach Pain', 'General Checkup'],
            'orthopedics' => ['Joint Pain', 'Back Pain', 'Fracture', 'Sprain', 'Arthritis', 'Sports Injury'],
            'gynecology' => ['Routine Checkup', 'Menstrual Problem', 'PCOS', 'Pregnancy', 'Infertility'],
        ];

        return $complaints[$specialty] ?? $complaints['general'];
    }

    public function create(Request $request, Patient $patient): RedirectResponse
    {
        $clinicId = auth()->user()->clinic_id;
        abort_unless($patient->clinic_id === $clinicId, 403);

        try {
            // Find today's appointment for this patient, if any
            $appointment = Appointment::where('clinic_id', $clinicId)
                ->where('patient_id', $patient->id)
                ->whereDate('scheduled_at', today())
                ->whereIn('status', ['checked_in', 'in_consultation', 'confirmed'])
                ->latest('scheduled_at')
                ->first();

            // Generate visit number
            $visitNumber = Visit::where('patient_id', $patient->id)->max('visit_number') + 1;

            $visit = Visit::create([
                'clinic_id'      => $clinicId,
                'patient_id'     => $patient->id,
                'doctor_id'      => auth()->id(),
                'appointment_id' => $appointment?->id,
                'visit_number'   => $visitNumber,
                'status'         => 'draft',
                'specialty'      => auth()->user()->specialty ?? 'general',
                'started_at'     => now(),
            ]);

            // Update appointment status if linked
            if ($appointment) {
                $appointment->update(['status' => 'in_consultation']);
            }

            // Update patient visit tracking
            $patient->increment('visit_count');
            $patient->update(['last_visit_date' => today()]);

            Log::info('EMR visit created via web', ['visit_id' => $visit->id, 'patient_id' => $patient->id]);

            return redirect()
                ->route('emr.show', [$patient, $visit])
                ->with('success', 'New visit started.');
        } catch (\Throwable $e) {
            Log::error('EMR create error', [
                'patient_id' => $patient->id, 
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            // In production, show generic error; in debug show actual error
            $errorMsg = config('app.debug') 
                ? 'Could not create visit: ' . $e->getMessage()
                : 'Could not create visit. Please try again.';

            return back()->with('error', $errorMsg);
        }
    }

    public function update(Request $request, Patient $patient, Visit $visit): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        abort_unless($patient->clinic_id === $clinicId && $visit->clinic_id === $clinicId, 403);
        abort_unless($visit->patient_id === $patient->id, 404);

        $validated = $request->validate([
            'structured_data'  => ['nullable', 'array'],
            'chief_complaint'  => ['nullable', 'string', 'max:1000'],
            'history'          => ['nullable', 'string'],
            'diagnosis_code'   => ['nullable', 'string', 'max:20'],
            'diagnosis_text'   => ['nullable', 'string', 'max:500'],
            'plan'             => ['nullable', 'string'],
            'followup_in_days' => ['nullable', 'integer', 'min:1'],
            'followup_date'    => ['nullable', 'date'],
        ]);

        try {
            $visit->update($validated);

            return response()->json([
                'saved' => true,
                'at'    => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('EMR auto-save error', ['visit_id' => $visit->id, 'error' => $e->getMessage()]);

            return response()->json(['saved' => false, 'error' => 'Save failed.'], 500);
        }
    }

    public function finalise(Request $request, Patient $patient, Visit $visit): RedirectResponse
    {
        $clinicId = auth()->user()->clinic_id;
        abort_unless($patient->clinic_id === $clinicId && $visit->clinic_id === $clinicId, 403);
        abort_unless($visit->patient_id === $patient->id, 404);

        Log::info('EMR finalise started', ['visit_id' => $visit->id, 'patient_id' => $patient->id]);

        try {
            // Mark visit as finalised (completed)
            $visit->update([
                'status'       => 'finalised',
                'finalised_at' => now(),
            ]);

            Log::info('Visit status updated to finalised', ['visit_id' => $visit->id]);

            // Update linked appointment
            if ($visit->appointment_id) {
                Appointment::where('id', $visit->appointment_id)
                    ->update(['status' => 'completed']);
                Log::info('Appointment status updated', ['appointment_id' => $visit->appointment_id]);
            }

            // Update patient follow-up date if set
            if ($visit->followup_date) {
                $patient->update(['next_followup_date' => $visit->followup_date]);
            }

            // Load prescription for PDF (optional - don't fail if no prescription)
            $visit->load(['prescriptions.drugs', 'doctor']);
            $clinic = auth()->user()->clinic;

            // Try to generate prescription PDF (non-blocking)
            try {
                if ($visit->prescriptions->isNotEmpty() && class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
                    $prescriptionPdf = Pdf::loadView('emr.prescription-pdf', compact('visit', 'patient', 'clinic'));
                    $pdfFilename = "prescription-{$visit->id}-{$patient->id}.pdf";
                    $pdfPath = storage_path("app/public/prescriptions/{$pdfFilename}");

                    // Ensure directory exists
                    if (!is_dir(dirname($pdfPath))) {
                        mkdir(dirname($pdfPath), 0755, true);
                    }
                    $prescriptionPdf->save($pdfPath);
                    Log::info('Prescription PDF generated', ['path' => $pdfPath]);
                }
            } catch (\Throwable $e) {
                Log::warning('PDF generation failed (non-blocking)', ['error' => $e->getMessage()]);
            }

            // Try to send WhatsApp (non-blocking)
            try {
                if ($this->whatsAppService && $clinic && $patient->phone) {
                    $this->whatsAppService->sendTemplate(
                        $clinic,
                        $patient,
                        'prescription_ready',
                        [
                            [
                                'type'       => 'body',
                                'parameters' => [
                                    ['type' => 'text', 'text' => $patient->name],
                                    ['type' => 'text', 'text' => $visit->diagnosis_text ?? 'your consultation'],
                                    ['type' => 'text', 'text' => $clinic->name ?? 'Clinic'],
                                ],
                            ],
                        ],
                        'prescription_ready',
                        $visit->id
                    );
                    Log::info('WhatsApp notification sent', ['patient_id' => $patient->id]);
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsApp send failed (non-blocking)', ['error' => $e->getMessage()]);
            }

            Log::info('Visit finalised successfully', ['visit_id' => $visit->id, 'patient_id' => $patient->id]);

            return redirect()
                ->route('patients.show', $patient)
                ->with('success', 'Visit finalised successfully.');
        } catch (\Throwable $e) {
            Log::error('EMR finalise error', [
                'visit_id' => $visit->id, 
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMsg = config('app.debug') 
                ? 'Could not finalise visit: ' . $e->getMessage()
                : 'Could not finalise visit. Please try again.';

            return back()->with('error', $errorMsg);
        }
    }

    /**
     * Add a lesion annotation to a visit
     */
    public function addLesion(Request $request, Patient $patient, Visit $visit): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        abort_unless($patient->clinic_id === $clinicId && $visit->clinic_id === $clinicId, 403);

        Log::info('EmrWebController@addLesion', ['visit_id' => $visit->id]);

        $validated = $request->validate([
            'body_region' => ['required', 'string', 'max:100'],
            'lesion_type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'size_cm' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'colour' => ['nullable', 'string', 'max:50'],
            'distribution' => ['nullable', 'string', 'max:100'],
            'surface' => ['nullable', 'string', 'max:100'],
            'x_pct' => ['nullable', 'numeric'],
            'y_pct' => ['nullable', 'numeric'],
            'view' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $lesion = VisitLesion::create([
                'visit_id' => $visit->id,
                'body_region' => $validated['body_region'],
                'lesion_type' => $validated['lesion_type'],
                'notes' => $validated['description'] ?? null,
                'size_cm' => $validated['size_cm'] ?? null,
                'colour' => $validated['colour'] ?? null,
                'distribution' => $validated['distribution'] ?? null,
                'surface' => $validated['surface'] ?? null,
                'x_pct' => $validated['x_pct'] ?? null,
                'y_pct' => $validated['y_pct'] ?? null,
                'view' => $validated['view'] ?? 'front',
            ]);

            Log::info('Lesion added', ['lesion_id' => $lesion->id, 'visit_id' => $visit->id]);

            return response()->json([
                'success' => true,
                'lesion' => $lesion,
            ]);
        } catch (\Throwable $e) {
            Log::error('Add lesion error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove a lesion annotation
     */
    public function removeLesion(Request $request, Patient $patient, Visit $visit, VisitLesion $lesion): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        abort_unless($visit->clinic_id === $clinicId && $lesion->visit_id === $visit->id, 403);

        Log::info('EmrWebController@removeLesion', ['lesion_id' => $lesion->id]);

        try {
            $lesion->delete();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Remove lesion error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Save grading scales for a visit
     */
    public function saveScales(Request $request, Patient $patient, Visit $visit): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        abort_unless($patient->clinic_id === $clinicId && $visit->clinic_id === $clinicId, 403);

        Log::info('EmrWebController@saveScales', ['visit_id' => $visit->id]);

        $validated = $request->validate([
            'scales' => ['required', 'array'],
            'scales.*.name' => ['required', 'string', 'max:50'],
            'scales.*.score' => ['required', 'numeric'],
            'scales.*.interpretation' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            // Delete existing scales and insert new ones
            VisitScale::where('visit_id', $visit->id)->delete();

            foreach ($validated['scales'] as $scaleData) {
                VisitScale::create([
                    'visit_id' => $visit->id,
                    'scale_name' => $scaleData['name'],
                    'score' => $scaleData['score'],
                    'interpretation' => $scaleData['interpretation'] ?? null,
                ]);
            }

            Log::info('Scales saved', ['visit_id' => $visit->id, 'count' => count($validated['scales'])]);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Save scales error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Save procedures for a visit
     */
    public function saveProcedures(Request $request, Patient $patient, Visit $visit): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        abort_unless($patient->clinic_id === $clinicId && $visit->clinic_id === $clinicId, 403);

        Log::info('EmrWebController@saveProcedures', ['visit_id' => $visit->id]);

        $validated = $request->validate([
            'procedures' => ['required', 'array'],
            'procedures.*.name' => ['required', 'string', 'max:100'],
            'procedures.*.agent' => ['nullable', 'string', 'max:100'],
            'procedures.*.areas' => ['nullable', 'string', 'max:200'],
            'procedures.*.session' => ['nullable', 'string', 'max:50'],
            'procedures.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            // Delete existing and insert new
            VisitProcedure::where('visit_id', $visit->id)->delete();

            foreach ($validated['procedures'] as $procData) {
                VisitProcedure::create([
                    'visit_id' => $visit->id,
                    'clinic_id' => $clinicId,
                    'procedure_name' => $procData['name'],
                    'body_region' => $procData['areas'] ?? null,
                    'notes' => $procData['notes'] ?? null,
                    'parameters' => [
                        'agent' => $procData['agent'] ?? null,
                        'session_number' => $procData['session'] ?? null,
                    ],
                ]);
            }

            Log::info('Procedures saved', ['visit_id' => $visit->id, 'count' => count($validated['procedures'])]);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Save procedures error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Save prescription for a visit
     */
    public function savePrescription(Request $request, Patient $patient, Visit $visit): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        abort_unless($patient->clinic_id === $clinicId && $visit->clinic_id === $clinicId, 403);

        Log::info('EmrWebController@savePrescription', ['visit_id' => $visit->id]);

        $validated = $request->validate([
            'drugs' => ['required', 'array', 'min:1'],
            'drugs.*.name' => ['required', 'string', 'max:200'],
            'drugs.*.generic' => ['nullable', 'string', 'max:200'],
            'drugs.*.dose' => ['required', 'string', 'max:100'],
            'drugs.*.frequency' => ['required', 'string', 'max:100'],
            'drugs.*.duration' => ['required', 'string', 'max:100'],
            'drugs.*.instructions' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            DB::beginTransaction();

            // Create or update prescription
            $prescription = Prescription::updateOrCreate(
                ['visit_id' => $visit->id],
                [
                    'clinic_id' => $clinicId,
                    'patient_id' => $patient->id,
                    'doctor_id' => auth()->id(),
                    'status' => 'draft',
                ]
            );

            // Delete existing drugs and add new ones
            PrescriptionDrug::where('prescription_id', $prescription->id)->delete();

            foreach ($validated['drugs'] as $index => $drugData) {
                PrescriptionDrug::create([
                    'prescription_id' => $prescription->id,
                    'drug_name' => $drugData['name'],
                    'generic_name' => $drugData['generic'] ?? null,
                    'dose' => $drugData['dose'],
                    'frequency' => $drugData['frequency'],
                    'duration' => $drugData['duration'],
                    'instructions' => $drugData['instructions'] ?? null,
                    'sort_order' => $index + 1,
                ]);
            }

            DB::commit();

            Log::info('Prescription saved', ['prescription_id' => $prescription->id, 'drugs_count' => count($validated['drugs'])]);

            return response()->json([
                'success' => true,
                'prescription_id' => $prescription->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Save prescription error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Search drugs from Indian drug database
     */
    public function searchDrugs(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        Log::info('EmrWebController@searchDrugs', ['query' => $query]);

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        try {
            $drugs = IndianDrug::where('brand_name', 'like', "%{$query}%")
                ->orWhere('generic_name', 'like', "%{$query}%")
                ->orWhere('composition', 'like', "%{$query}%")
                ->select('id', 'brand_name', 'generic_name', 'composition', 'manufacturer', 'strength')
                ->limit(20)
                ->get();

            return response()->json($drugs);
        } catch (\Throwable $e) {
            // If IndianDrug table doesn't exist, return sample data
            Log::warning('Drug search failed, returning sample data', ['error' => $e->getMessage()]);
            
            $sampleDrugs = collect([
                ['id' => 1, 'brand_name' => 'Adapalene 0.1% gel', 'generic_name' => 'Adapalene', 'composition' => 'Adapalene 0.1%', 'manufacturer' => 'Differin', 'strength' => '0.1%'],
                ['id' => 2, 'brand_name' => 'Tretinoin 0.025%', 'generic_name' => 'Tretinoin', 'composition' => 'Tretinoin 0.025%', 'manufacturer' => 'Retin-A', 'strength' => '0.025%'],
                ['id' => 3, 'brand_name' => 'Clindamycin 1% lotion', 'generic_name' => 'Clindamycin', 'composition' => 'Clindamycin 1%', 'manufacturer' => 'Clindac A', 'strength' => '1%'],
                ['id' => 4, 'brand_name' => 'Doxycycline 100mg', 'generic_name' => 'Doxycycline', 'composition' => 'Doxycycline 100mg', 'manufacturer' => 'Doxt-SL', 'strength' => '100mg'],
                ['id' => 5, 'brand_name' => 'Azithromycin 500mg', 'generic_name' => 'Azithromycin', 'composition' => 'Azithromycin 500mg', 'manufacturer' => 'Azithral', 'strength' => '500mg'],
                ['id' => 6, 'brand_name' => 'Metronidazole 400mg', 'generic_name' => 'Metronidazole', 'composition' => 'Metronidazole 400mg', 'manufacturer' => 'Flagyl', 'strength' => '400mg'],
                ['id' => 7, 'brand_name' => 'Sunscreen SPF 50+', 'generic_name' => 'Sunscreen', 'composition' => 'Broad spectrum UVA+UVB', 'manufacturer' => 'Various', 'strength' => 'SPF 50+'],
                ['id' => 8, 'brand_name' => 'Betamethasone 0.05%', 'generic_name' => 'Betamethasone', 'composition' => 'Betamethasone 0.05%', 'manufacturer' => 'Betnovate', 'strength' => '0.05%'],
                ['id' => 9, 'brand_name' => 'Calcipotriol 0.005%', 'generic_name' => 'Calcipotriol', 'composition' => 'Calcipotriol 0.005%', 'manufacturer' => 'Daivonex', 'strength' => '0.005%'],
                ['id' => 10, 'brand_name' => 'Tacrolimus 0.1%', 'generic_name' => 'Tacrolimus', 'composition' => 'Tacrolimus 0.1%', 'manufacturer' => 'Protopic', 'strength' => '0.1%'],
            ])->filter(function ($drug) use ($query) {
                return str_contains(strtolower($drug['brand_name']), strtolower($query)) ||
                       str_contains(strtolower($drug['generic_name']), strtolower($query));
            })->values();

            return response()->json($sampleDrugs);
        }
    }
}
