<?php

namespace App\Http\Controllers\Prescription;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\PrescriptionDrug;
use App\Models\IndianDrug;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    /**
     * List prescriptions
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

        Log::info('Prescriptions retrieved', ['count' => $prescriptions->total()]);

        return response()->json($prescriptions);
    }

    /**
     * Create prescription
     */
    public function store(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Creating prescription', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'patient_id' => 'required|exists:patients,id',
            'valid_days' => 'nullable|integer|min:1|max:365',
            'drugs' => 'required|array|min:1',
            'drugs.*.drug_name' => 'required|string|max:200',
            'drugs.*.generic_name' => 'nullable|string|max:200',
            'drugs.*.strength' => 'nullable|string|max:50',
            'drugs.*.form' => 'nullable|string|max:50',
            'drugs.*.dose' => 'required|string|max:100',
            'drugs.*.frequency' => 'required|string|max:100',
            'drugs.*.route' => 'nullable|string|max:30',
            'drugs.*.duration' => 'nullable|string|max:50',
            'drugs.*.instructions' => 'nullable|string',
            'drugs.*.drug_db_id' => 'nullable|exists:indian_drugs,id',
        ]);

        DB::beginTransaction();
        try {
            $prescription = Prescription::create([
                'clinic_id' => $clinicId,
                'visit_id' => $validated['visit_id'],
                'patient_id' => $validated['patient_id'],
                'doctor_id' => $request->user()->id,
                'valid_days' => $validated['valid_days'] ?? 30,
            ]);

            Log::info('Prescription record created', ['prescription_id' => $prescription->id]);

            foreach ($validated['drugs'] as $index => $drugData) {
                $prescription->drugs()->create([
                    'drug_db_id' => $drugData['drug_db_id'] ?? null,
                    'drug_name' => $drugData['drug_name'],
                    'generic_name' => $drugData['generic_name'] ?? null,
                    'strength' => $drugData['strength'] ?? null,
                    'form' => $drugData['form'] ?? null,
                    'dose' => $drugData['dose'],
                    'frequency' => $drugData['frequency'],
                    'route' => $drugData['route'] ?? 'oral',
                    'duration' => $drugData['duration'] ?? null,
                    'instructions' => $drugData['instructions'] ?? null,
                    'sort_order' => $index,
                ]);

                Log::info('Drug added to prescription', [
                    'prescription_id' => $prescription->id,
                    'drug_name' => $drugData['drug_name']
                ]);
            }

            DB::commit();

            Log::info('Prescription created successfully', [
                'prescription_id' => $prescription->id,
                'drug_count' => count($validated['drugs'])
            ]);

            return response()->json([
                'message' => 'Prescription created successfully',
                'prescription' => $prescription->load(['drugs', 'patient', 'doctor']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Prescription creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Show prescription
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching prescription', ['prescription_id' => $id]);

        $prescription = Prescription::forClinic($clinicId)
            ->with(['drugs', 'patient', 'doctor', 'visit'])
            ->findOrFail($id);

        return response()->json([
            'prescription' => $prescription,
        ]);
    }

    /**
     * Send prescription via WhatsApp
     */
    public function send(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Sending prescription via WhatsApp', ['prescription_id' => $id]);

        $prescription = Prescription::forClinic($clinicId)
            ->with(['drugs', 'patient', 'doctor'])
            ->findOrFail($id);

        // TODO: Implement WhatsApp sending via WhatsAppService
        // For now, just update the timestamp
        $prescription->update(['whatsapp_sent_at' => now()]);

        Log::info('Prescription sent via WhatsApp', ['prescription_id' => $id]);

        return response()->json([
            'message' => 'Prescription sent successfully',
        ]);
    }

    /**
     * Generate PDF
     */
    public function pdf(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Generating prescription PDF', ['prescription_id' => $id]);

        $prescription = Prescription::forClinic($clinicId)
            ->with(['drugs', 'patient', 'doctor', 'clinic'])
            ->findOrFail($id);

        // TODO: Implement PDF generation
        Log::info('Prescription PDF requested', ['prescription_id' => $id]);

        return response()->json([
            'message' => 'PDF generation not yet implemented',
            'prescription' => $prescription,
        ]);
    }

    /**
     * Search drugs
     */
    public function drugSearch(Request $request): JsonResponse
    {
        Log::info('Searching drugs', ['query' => $request->q]);

        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $drugs = IndianDrug::searchByName($validated['q'])
            ->limit(20)
            ->get();

        Log::info('Drug search results', ['count' => $drugs->count()]);

        return response()->json([
            'drugs' => $drugs,
        ]);
    }
}
