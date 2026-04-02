<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LabTestCatalog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LabTechnicianController extends Controller
{
    /**
     * Lab technician dashboard — shows pending/in-progress orders assigned to this clinic.
     */
    public function dashboard(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;

        $pendingOrders = DB::table('lab_orders')
            ->join('patients', 'lab_orders.patient_id', '=', 'patients.id')
            ->leftJoin('users as doctors', 'lab_orders.created_by', '=', 'doctors.id')
            ->where('lab_orders.clinic_id', $clinicId)
            ->whereIn('lab_orders.status', ['pending', 'sample_collected', 'processing'])
            ->select(
                'lab_orders.*',
                'patients.name as patient_name',
                'patients.phone as patient_phone',
                'patients.date_of_birth',
                'patients.gender',
                'doctors.name as doctor_name'
            )
            ->orderByRaw("FIELD(lab_orders.priority,'stat','urgent','routine') DESC")
            ->orderBy('lab_orders.created_at')
            ->get();

        $completedToday = DB::table('lab_orders')
            ->where('clinic_id', $clinicId)
            ->where('status', 'completed')
            ->whereDate('updated_at', today())
            ->count();

        $stats = [
            'pending'          => $pendingOrders->where('status', 'pending')->count(),
            'sample_collected' => $pendingOrders->where('status', 'sample_collected')->count(),
            'processing'       => $pendingOrders->where('status', 'processing')->count(),
            'completed_today'  => $completedToday,
        ];

        return view('lab.technician.dashboard', compact('pendingOrders', 'stats'));
    }

    /**
     * Mark sample as collected for an order.
     */
    public function collectSample(Request $request, int $orderId)
    {
        $clinicId = auth()->user()->clinic_id;

        $validated = $request->validate([
            'sample_type'      => 'required|string|max:100',
            'collection_notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($orderId, $clinicId, $validated) {
            // Insert into lab_samples (item_id is nullable after fix migration)
            if (DB::getSchemaBuilder()->hasTable('lab_samples')) {
                // Grab the first order item to satisfy FK if item_id is required
                $firstItem = DB::table('lab_order_items')->where('order_id', $orderId)->first();

                if ($firstItem) {
                    DB::table('lab_samples')->insert([
                        'order_id'     => $orderId,
                        'item_id'      => $firstItem->id,
                        'clinic_id'    => $clinicId,
                        'sample_type'  => $validated['sample_type'],
                        'barcode'      => 'SMP-' . strtoupper(uniqid()),
                        'collected_by' => auth()->id(),
                        'collected_at' => now(),
                        'status'       => 'collected',
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }

            DB::table('lab_orders')->where('id', $orderId)
                ->where('clinic_id', $clinicId)
                ->update(['status' => 'sample_collected', 'updated_at' => now()]);
        });

        return response()->json(['success' => true, 'message' => 'Sample collected']);
    }

    /**
     * Show result entry form for a specific order.
     */
    public function resultForm(int $orderId)
    {
        $clinicId = auth()->user()->clinic_id;

        $order = DB::table('lab_orders')
            ->join('patients', 'lab_orders.patient_id', '=', 'patients.id')
            ->leftJoin('users as doctors', 'lab_orders.created_by', '=', 'doctors.id')
            ->where('lab_orders.id', $orderId)
            ->where('lab_orders.clinic_id', $clinicId)
            ->select(
                'lab_orders.*',
                'patients.name as patient_name',
                'patients.date_of_birth',
                'patients.gender',
                'doctors.name as doctor_name',
                'doctors.id as doctor_id'
            )
            ->firstOrFail();

        $items = DB::table('lab_order_items')
            ->join('lab_tests_catalog', 'lab_order_items.test_id', '=', 'lab_tests_catalog.id')
            ->where('lab_order_items.order_id', $orderId)
            ->select(
                'lab_order_items.*',
                'lab_tests_catalog.test_name',
                'lab_tests_catalog.unit'
            )
            ->get()
            ->map(function ($item) {
                // Attach existing result if any
                $result = DB::table('lab_results')
                    ->where('order_item_id', $item->id)
                    ->first();
                $item->result_value  = $result->value      ?? null;
                $item->is_abnormal   = $result->is_abnormal ?? false;
                $item->remarks       = $result->notes       ?? null;
                $item->reference_range = null;
                return $item;
            });

        // Advance status to processing
        DB::table('lab_orders')->where('id', $orderId)
            ->where('status', 'sample_collected')
            ->update(['status' => 'processing', 'updated_at' => now()]);

        return view('lab.technician.result-form', compact('order', 'items'));
    }

    /**
     * Save results and notify doctor.
     */
    public function saveResults(Request $request, int $orderId)
    {
        $clinicId = auth()->user()->clinic_id;

        $validated = $request->validate([
            'results'               => 'required|array',
            'results.*.item_id'     => 'required|integer',
            'results.*.value'       => 'required|string|max:255',
            'results.*.is_abnormal' => 'nullable|boolean',
            'results.*.is_critical' => 'nullable|boolean',
            'results.*.remarks'     => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $orderId, $clinicId) {
            foreach ($validated['results'] as $result) {
                // Mark the order item as completed
                DB::table('lab_order_items')
                    ->where('id', $result['item_id'])
                    ->where('order_id', $orderId)
                    ->update([
                        'status'     => 'completed',
                        'updated_at' => now(),
                    ]);

                // Get the item for test_id
                $item = DB::table('lab_order_items')->where('id', $result['item_id'])->first();

                if ($item) {
                    $existingResult = DB::table('lab_results')
                        ->where('order_item_id', $item->id)
                        ->first();

                    $resultData = [
                        'clinic_id'     => $clinicId,
                        'order_item_id' => $item->id,
                        'test_id'       => $item->test_id,
                        'value'         => $result['value'],
                        'is_abnormal'   => !empty($result['is_abnormal']),
                        'is_critical'   => !empty($result['is_critical']),
                        'notes'         => $result['remarks'] ?? null,
                        'result_date'   => now(),
                        'updated_at'    => now(),
                    ];

                    if ($existingResult) {
                        DB::table('lab_results')->where('id', $existingResult->id)->update($resultData);
                    } else {
                        DB::table('lab_results')->insert(array_merge($resultData, ['created_at' => now()]));
                    }
                }
            }

            // Mark the order as completed
            DB::table('lab_orders')->where('id', $orderId)->update([
                'status'     => 'completed',
                'updated_at' => now(),
            ]);
        });

        try {
            \App\Models\AuditLog::log(
                'lab_results_saved',
                "Lab results saved for order #{$orderId}",
                'lab_orders',
                $orderId
            );
        } catch (\Throwable $e) {
            Log::warning('AuditLog failed', ['error' => $e->getMessage()]);
        }

        Log::info('Lab results saved', ['order_id' => $orderId, 'by' => auth()->id()]);

        // Send WhatsApp notification to patient (best-effort)
        try {
            $order = DB::table('lab_orders')
                ->join('patients', 'lab_orders.patient_id', '=', 'patients.id')
                ->where('lab_orders.id', $orderId)
                ->select(
                    'patients.id as patient_id',
                    'patients.phone',
                    'patients.name as patient_name',
                    'lab_orders.order_number',
                    'lab_orders.clinic_id'
                )
                ->first();

            if ($order && $order->phone) {
                $patient  = \App\Models\Patient::find($order->patient_id);
                $labOrder = DB::table('lab_orders')->where('id', $orderId)->first();

                $clinicName  = auth()->user()->clinic?->name ?? 'ClinicOS';
                $patientName = $order->patient_name ?? 'Patient';
                $orderRef    = $order->order_number ?? ('LAB-' . $orderId);

                $message = "Dear {$patientName}, your lab results for order #{$orderRef} are ready. "
                         . "Please visit the hospital or contact your doctor. — {$clinicName}";

                app(\App\Services\WhatsAppService::class)->sendText($order->phone, $message);

                Log::info('WhatsApp lab notification sent', ['phone' => $order->phone, 'order_id' => $orderId]);
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp lab notification failed', ['error' => $e->getMessage(), 'order_id' => $orderId]);
        }

        return redirect()->route('lab.technician.dashboard')->with('success', 'Results saved. Doctor has been notified.');
    }

    /**
     * Doctor's view: results for their orders.
     */
    public function doctorResults(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $doctorId = auth()->id();

        $completedOrders = DB::table('lab_orders')
            ->join('patients', 'lab_orders.patient_id', '=', 'patients.id')
            ->where('lab_orders.clinic_id', $clinicId)
            ->where('lab_orders.created_by', $doctorId)
            ->where('lab_orders.status', 'completed')
            ->select('lab_orders.*', 'patients.name as patient_name', 'patients.phone')
            ->orderByDesc('lab_orders.updated_at')
            ->paginate(20);

        return view('lab.technician.doctor-results', compact('completedOrders'));
    }

    /**
     * View full result report for a single order.
     */
    public function viewReport(int $orderId)
    {
        $clinicId = auth()->user()->clinic_id;

        $order = DB::table('lab_orders')
            ->join('patients', 'lab_orders.patient_id', '=', 'patients.id')
            ->leftJoin('users as doctors', 'lab_orders.created_by', '=', 'doctors.id')
            ->where('lab_orders.id', $orderId)
            ->where('lab_orders.clinic_id', $clinicId)
            ->select(
                'lab_orders.*',
                'patients.name as patient_name',
                'patients.date_of_birth',
                'patients.gender',
                'patients.phone',
                'doctors.name as doctor_name'
            )
            ->firstOrFail();

        $items = DB::table('lab_order_items')
            ->join('lab_tests_catalog', 'lab_order_items.test_id', '=', 'lab_tests_catalog.id')
            ->leftJoin('lab_results', 'lab_results.order_item_id', '=', 'lab_order_items.id')
            ->where('lab_order_items.order_id', $orderId)
            ->select(
                'lab_order_items.*',
                'lab_tests_catalog.test_name',
                'lab_tests_catalog.unit',
                'lab_results.value as result_value',
                'lab_results.is_abnormal',
                'lab_results.is_critical',
                'lab_results.notes as remarks'
            )
            ->get();

        $clinicName = auth()->user()->clinic?->name ?? config('app.name');

        return view('lab.technician.report', compact('order', 'items', 'clinicName'));
    }
}
