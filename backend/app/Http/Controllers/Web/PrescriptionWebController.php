<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IndianDrug;
use App\Models\Patient;
use App\Models\PrescriptionItem;
use App\Models\PrescriptionTemplate;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PrescriptionWebController extends Controller
{
    /**
     * Display prescription listing/dashboard
     */
    public function index(Request $request): View
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('PrescriptionWebController@index', [
            'clinic_id' => $clinicId,
            'query' => $request->query(),
        ]);

        $itemsTableReady = Schema::hasTable('prescription_items');
        $templatesTableReady = Schema::hasTable('prescription_templates');
        $visitWhatsappReady = Schema::hasTable('visits')
            && Schema::hasColumn('visits', 'prescription_sent_whatsapp');

        $stats = [
            'today' => 0,
            'week' => 0,
            'sent_via_whatsapp' => 0,
            'templates' => 0,
        ];

        try {
            if ($itemsTableReady) {
                $stats['today'] = Visit::where('clinic_id', $clinicId)
                    ->whereDate('created_at', today())
                    ->whereHas('prescriptionItems')
                    ->count();
                $stats['week'] = Visit::where('clinic_id', $clinicId)
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->whereHas('prescriptionItems')
                    ->count();
            }
            if ($visitWhatsappReady) {
                $stats['sent_via_whatsapp'] = Visit::where('clinic_id', $clinicId)
                    ->whereMonth('created_at', now()->month)
                    ->where('prescription_sent_whatsapp', true)
                    ->count();
            }
            if ($templatesTableReady) {
                $stats['templates'] = PrescriptionTemplate::where('clinic_id', $clinicId)->active()->count();
            }
        } catch (\Throwable $e) {
            Log::error('PrescriptionWebController@index stats failed', [
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
            ]);
        }

        $topDrugs = collect();
        try {
            if ($itemsTableReady && Schema::hasTable('visits')) {
                $topDrugs = DB::table('prescription_items')
                    ->join('visits', 'prescription_items.visit_id', '=', 'visits.id')
                    ->where('visits.clinic_id', $clinicId)
                    ->whereMonth('prescription_items.created_at', now()->month)
                    ->select('prescription_items.drug_name', DB::raw('COUNT(*) as count'))
                    ->groupBy('prescription_items.drug_name')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->get();
            }
        } catch (\Throwable $e) {
            Log::error('PrescriptionWebController@index topDrugs failed', [
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
            ]);
        }

        $recentPrescriptions = collect();
        try {
            if ($itemsTableReady) {
                $q = Visit::with(['patient', 'doctor', 'prescriptionItems'])
                    ->where('clinic_id', $clinicId)
                    ->whereHas('prescriptionItems');

                if ($request->filled('patient_id')) {
                    $q->where('patient_id', (int) $request->input('patient_id'));
                }
                if ($request->filled('doctor_id')) {
                    $q->where('doctor_id', (int) $request->input('doctor_id'));
                }
                if ($request->filled('from')) {
                    $q->whereDate('created_at', '>=', $request->input('from'));
                }
                if ($request->filled('to')) {
                    $q->whereDate('created_at', '<=', $request->input('to'));
                }
                if ($visitWhatsappReady) {
                    if ($request->input('sent') === 'yes') {
                        $q->where('prescription_sent_whatsapp', true);
                    } elseif ($request->input('sent') === 'no') {
                        $q->where(function ($sub) {
                            $sub->where('prescription_sent_whatsapp', false)
                                ->orWhereNull('prescription_sent_whatsapp');
                        });
                    }
                }

                $recentPrescriptions = $q->orderByDesc('created_at')->limit(20)->get();
            }
        } catch (\Throwable $e) {
            Log::error('PrescriptionWebController@index recent list failed', [
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
            ]);
        }

        $templates = collect();
        try {
            if ($templatesTableReady) {
                $templates = PrescriptionTemplate::where('clinic_id', $clinicId)
                    ->active()
                    ->orderBy('name')
                    ->get();
            }
        } catch (\Throwable $e) {
            Log::error('PrescriptionWebController@index templates failed', [
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
            ]);
        }

        $patients = Patient::where('clinic_id', $clinicId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $doctors = User::where('clinic_id', $clinicId)
            ->whereIn('role', ['doctor', 'admin'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        Log::info('PrescriptionWebController@index loaded', [
            'recent_count' => $recentPrescriptions->count(),
            'items_table' => $itemsTableReady,
            'visit_whatsapp_cols' => $visitWhatsappReady,
        ]);

        return view('prescriptions.index', [
            'stats' => $stats,
            'topDrugs' => $topDrugs,
            'recentPrescriptions' => $recentPrescriptions,
            'templates' => $templates,
            'patients' => $patients,
            'doctors' => $doctors,
            'prescriptionItemsTableReady' => $itemsTableReady,
            'visitPrescriptionColumnsReady' => $visitWhatsappReady,
        ]);
    }

    /**
     * Search drugs via AJAX
     */
    public function searchDrugs(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        Log::info('PrescriptionWebController: Searching drugs with query: ' . $query);

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        if (! Schema::hasTable('indian_drugs')) {
            Log::warning('PrescriptionWebController: searchDrugs — indian_drugs table missing');

            return response()->json([]);
        }

        $driver = Schema::getConnection()->getDriverName();
        Log::info('PrescriptionWebController: searchDrugs driver', ['driver' => $driver]);

        $drugs = IndianDrug::where('is_active', true)
            ->where(function ($q) use ($query, $driver) {
                $q->where('generic_name', 'LIKE', "%{$query}%");
                if ($driver === 'mysql') {
                    $q->orWhereRaw("JSON_SEARCH(brand_names, 'one', ?) IS NOT NULL", ["%{$query}%"]);
                } else {
                    $q->orWhere('brand_names', 'LIKE', '%'.$query.'%');
                }
            })
            ->orderBy('generic_name')
            ->limit(20)
            ->get()
            ->map(function ($drug) {
                return [
                    'id' => $drug->id,
                    'generic_name' => $drug->generic_name,
                    'brand_names' => $drug->brand_names,
                    'first_brand' => $drug->first_brand,
                    'form' => $drug->form,
                    'strength' => $drug->strength,
                    'display_name' => $drug->display_name,
                    'drug_class' => $drug->drug_class,
                    'schedule' => $drug->schedule,
                    'common_dosages' => $drug->common_dosages,
                    'is_controlled' => $drug->is_controlled,
                ];
            });

        Log::info('PrescriptionWebController: Found ' . count($drugs) . ' drugs');

        return response()->json($drugs);
    }

    /**
     * Check drug interactions
     */
    public function checkInteractions(Request $request): JsonResponse
    {
        $drugIds = $request->input('drug_ids', []);
        Log::info('PrescriptionWebController: Checking interactions for drugs: ' . implode(',', $drugIds));

        if (count($drugIds) < 2) {
            return response()->json(['interactions' => []]);
        }

        if (! Schema::hasTable('drug_interactions') || ! Schema::hasTable('indian_drugs')) {
            Log::warning('PrescriptionWebController: checkInteractions tables missing');

            return response()->json(['interactions' => []]);
        }

        $interactions = DB::table('drug_interactions')
            ->join('indian_drugs as drug_a', 'drug_interactions.drug_a_id', '=', 'drug_a.id')
            ->join('indian_drugs as drug_b', 'drug_interactions.drug_b_id', '=', 'drug_b.id')
            ->whereIn('drug_a_id', $drugIds)
            ->whereIn('drug_b_id', $drugIds)
            ->select(
                'drug_a.generic_name as drug_a_name',
                'drug_b.generic_name as drug_b_name',
                'drug_interactions.severity',
                'drug_interactions.description',
                'drug_interactions.management'
            )
            ->get();

        Log::info('PrescriptionWebController: Found ' . count($interactions) . ' interactions');

        return response()->json(['interactions' => $interactions]);
    }

    /**
     * Save prescription items for a visit
     */
    public function savePrescription(Request $request, Visit $visit): JsonResponse
    {
        Log::info('PrescriptionWebController: Saving prescription for visit: ' . $visit->id);

        if (! Schema::hasTable('prescription_items')) {
            Log::warning('PrescriptionWebController: savePrescription blocked — prescription_items missing', ['visit_id' => $visit->id]);

            return response()->json([
                'success' => false,
                'error' => 'Prescription storage is not installed. Run php artisan migrate.',
            ], 503);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.drug_id' => 'nullable|exists:indian_drugs,id',
            'items.*.drug_name' => 'required|string|max:255',
            'items.*.dosage' => 'required|string|max:100',
            'items.*.frequency' => 'required|string|max:50',
            'items.*.duration' => 'required|string|max:50',
            'items.*.route' => 'nullable|string|max:50',
            'items.*.instructions' => 'nullable|string|max:500',
            'items.*.quantity' => 'nullable|integer',
            'items.*.is_substitutable' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            PrescriptionItem::where('visit_id', $visit->id)->delete();

            foreach ($validated['items'] as $index => $item) {
                PrescriptionItem::create([
                    'visit_id' => $visit->id,
                    'drug_id' => $item['drug_id'] ?? null,
                    'drug_name' => $item['drug_name'],
                    'dosage' => $item['dosage'],
                    'frequency' => $item['frequency'],
                    'duration' => $item['duration'],
                    'route' => $item['route'] ?? 'oral',
                    'instructions' => $item['instructions'] ?? null,
                    'quantity' => $item['quantity'] ?? null,
                    'is_substitutable' => $item['is_substitutable'] ?? true,
                    'sort_order' => $index,
                ]);
            }

            DB::commit();

            Log::info('PrescriptionWebController: Saved ' . count($validated['items']) . ' prescription items');

            return response()->json([
                'success' => true,
                'message' => 'Prescription saved successfully',
                'count' => count($validated['items']),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PrescriptionWebController: Error saving prescription: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get prescription templates
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        $specialty = $request->input('specialty');

        Log::info('PrescriptionWebController: Loading templates for clinic: ' . $clinicId . ', specialty: ' . $specialty);

        $query = PrescriptionTemplate::where('clinic_id', $clinicId)->active();

        if ($specialty) {
            $query->forSpecialty($specialty);
        }

        $templates = $query->orderBy('name')->get();

        Log::info('PrescriptionWebController: Found ' . $templates->count() . ' templates');

        return response()->json($templates);
    }

    /**
     * Save a prescription template
     */
    public function saveTemplate(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('PrescriptionWebController: Saving template for clinic: ' . $clinicId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'diagnosis' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:100',
            'medications' => 'required|array|min:1',
            'instructions' => 'nullable|string|max:1000',
        ]);

        try {
            $template = PrescriptionTemplate::create([
                'clinic_id' => $clinicId,
                'created_by' => auth()->id(),
                'name' => $validated['name'],
                'diagnosis' => $validated['diagnosis'] ?? null,
                'specialty' => $validated['specialty'] ?? null,
                'medications' => $validated['medications'],
                'instructions' => $validated['instructions'] ?? null,
            ]);

            Log::info('PrescriptionWebController: Template created with ID: ' . $template->id);

            return response()->json([
                'success' => true,
                'message' => 'Template saved successfully',
                'template' => $template,
            ]);

        } catch (\Throwable $e) {
            Log::error('PrescriptionWebController: Error saving template: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a prescription template
     */
    public function deleteTemplate(PrescriptionTemplate $template): JsonResponse
    {
        Log::info('PrescriptionWebController: Deleting template: ' . $template->id);

        if ($template->clinic_id !== auth()->user()->clinic_id) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $template->delete();

        return response()->json(['success' => true, 'message' => 'Template deleted']);
    }

    /**
     * Generate prescription PDF
     */
    public function generatePdf(Visit $visit)
    {
        Log::info('PrescriptionWebController: Generating PDF for visit: ' . $visit->id);

        $visit->load(['patient', 'prescriptionItems.drug', 'clinic']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('prescriptions.pdf', [
            'visit' => $visit,
            'patient' => $visit->patient,
            'items' => $visit->prescriptionItems,
            'clinic' => $visit->clinic,
            'doctor' => auth()->user(),
        ]);

        $filename = 'prescription_' . $visit->patient->name . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Spectacle (distance / near) prescription PDF from ophthalmology structured data.
     */
    public function spectaclePdf(Visit $visit)
    {
        Log::info('PrescriptionWebController@spectaclePdf', ['visit_id' => $visit->id, 'user' => auth()->id()]);
        abort_unless($visit->clinic_id === auth()->user()->clinic_id, 403);

        $visit->load(['patient', 'clinic', 'doctor']);
        $payload = $this->buildSpectaclePayload($visit);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('prescriptions.spectacle-pdf', [
            'visit' => $visit,
            'patient' => $visit->patient,
            'clinic' => $visit->clinic,
            'doctor' => $visit->doctor ?? auth()->user(),
            'spectacle' => $payload,
        ]);

        $filename = 'spectacle_rx_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $visit->patient->name) . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Contact lens prescription PDF from ophthalmology structured data.
     */
    public function contactLensPdf(Visit $visit)
    {
        Log::info('PrescriptionWebController@contactLensPdf', ['visit_id' => $visit->id, 'user' => auth()->id()]);
        abort_unless($visit->clinic_id === auth()->user()->clinic_id, 403);

        $visit->load(['patient', 'clinic', 'doctor']);
        $payload = $this->buildContactLensPayload($visit);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('prescriptions.contact-lens-pdf', [
            'visit' => $visit,
            'patient' => $visit->patient,
            'clinic' => $visit->clinic,
            'doctor' => $visit->doctor ?? auth()->user(),
            'contactLens' => $payload,
        ]);

        $filename = 'contact_lens_rx_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $visit->patient->name) . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSpectaclePayload(Visit $visit): array
    {
        $sd = $visit->structured_data ?? [];
        $spec = data_get($sd, 'ophthal.spectacle_rx');
        if (is_array($spec) && !empty($spec)) {
            Log::info('PrescriptionWebController: using ophthal.spectacle_rx');

            return array_merge(['source' => 'spectacle_rx'], $spec);
        }

        $ref = data_get($sd, 'ophthal.refraction');
        if (is_array($ref)) {
            Log::info('PrescriptionWebController: spectacle payload derived from refraction');

            return [
                'source' => 'refraction',
                'type' => $ref['type'] ?? '',
                'od' => $ref['od'] ?? [],
                'os' => $ref['os'] ?? [],
                'pdDistance' => $ref['pdDistance'] ?? '',
                'pdNear' => $ref['pdNear'] ?? '',
            ];
        }

        Log::warning('PrescriptionWebController: spectacle payload empty', ['visit_id' => $visit->id]);

        return ['source' => 'empty', 'od' => [], 'os' => []];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContactLensPayload(Visit $visit): array
    {
        $sd = $visit->structured_data ?? [];
        $cl = data_get($sd, 'ophthal.contact_lens_rx');
        if (is_array($cl) && !empty($cl)) {
            Log::info('PrescriptionWebController: using ophthal.contact_lens_rx');

            return array_merge(['source' => 'contact_lens_rx'], $cl);
        }

        Log::warning('PrescriptionWebController: contact lens payload empty', ['visit_id' => $visit->id]);

        return [
            'source' => 'empty',
            'od' => [],
            'os' => [],
            'notes' => 'Enter contact lens parameters in the ophthalmology EMR and save.',
        ];
    }

    /**
     * Send prescription via WhatsApp
     */
    public function sendWhatsApp(Request $request, Visit $visit): JsonResponse
    {
        Log::info('PrescriptionWebController: Sending WhatsApp for visit: ' . $visit->id);

        $visit->load(['patient', 'prescriptionItems']);

        $patient = $visit->patient;

        if (!$patient->phone) {
            return response()->json(['success' => false, 'error' => 'Patient phone number not available'], 400);
        }

        $prescriptionText = "📋 *Prescription*\n";
        $prescriptionText .= "Patient: {$patient->name}\n";
        $prescriptionText .= "Date: " . now()->format('d M Y') . "\n\n";
        $prescriptionText .= "*Medications:*\n";

        foreach ($visit->prescriptionItems as $index => $item) {
            $prescriptionText .= ($index + 1) . ". {$item->drug_name}\n";
            $prescriptionText .= "   {$item->dosage} - {$item->frequency_label}\n";
            $prescriptionText .= "   Duration: {$item->duration}\n";
            if ($item->instructions) {
                $prescriptionText .= "   Instructions: {$item->instructions}\n";
            }
            $prescriptionText .= "\n";
        }

        auth()->user()->loadMissing('clinic');
        $clinicName = auth()->user()->clinic?->name ?? 'Clinic';
        $prescriptionText .= "\n🏥 " . $clinicName;
        Log::info('PrescriptionWebController: sendWhatsApp clinic resolved', ['visit_id' => $visit->id, 'clinic' => $clinicName]);

        $phone = preg_replace('/[^0-9]/', '', $patient->phone);
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        $waUrl = "https://api.whatsapp.com/send?phone={$phone}&text=" . urlencode($prescriptionText);

        try {
            if (Schema::hasColumn('visits', 'prescription_sent_whatsapp')) {
                $visit->update([
                    'prescription_sent_whatsapp' => true,
                    'prescription_sent_at' => now(),
                ]);
            } else {
                Log::warning('PrescriptionWebController: sendWhatsApp — visit tracking columns missing', ['visit_id' => $visit->id]);
            }
        } catch (\Throwable $e) {
            Log::error('PrescriptionWebController: sendWhatsApp persist failed', [
                'visit_id' => $visit->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('PrescriptionWebController: WhatsApp URL generated for visit', ['visit_id' => $visit->id]);

        return response()->json([
            'success' => true,
            'whatsapp_url' => $waUrl,
        ]);
    }
}
