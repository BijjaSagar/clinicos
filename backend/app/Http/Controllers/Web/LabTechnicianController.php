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
            ->join('users as doctors', 'lab_orders.ordered_by', '=', 'doctors.id')
            ->where('lab_orders.clinic_id', $clinicId)
            ->whereIn('lab_orders.status', ['pending', 'sample_collected', 'processing'])
            ->select(
                'lab_orders.*',
                'patients.full_name as patient_name',
                'patients.phone as patient_phone',
                'patients.date_of_birth',
                'patients.gender',
                'doctors.name as doctor_name'
            )
            ->orderByRaw("FIELD(lab_orders.priority,'stat','urgent','routine')")
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
            'sample_type'    => 'required|string|max:100',
            'collection_notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($orderId, $clinicId, $validated) {
            // Insert into lab_samples
            DB::table('lab_samples')->insert([
                'lab_order_id'  => $orderId,
                'clinic_id'     => $clinicId,
                'sample_type'   => $validated['sample_type'],
                'sample_id'     => 'SMP-' . strtoupper(uniqid()),
                'collected_by'  => auth()->id(),
                'collected_at'  => now(),
                'status'        => 'collected',
                'notes'         => $validated['collection_notes'] ?? null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

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
            ->join('users as doctors', 'lab_orders.ordered_by', '=', 'doctors.id')
            ->where('lab_orders.id', $orderId)
            ->where('lab_orders.clinic_id', $clinicId)
            ->select(
                'lab_orders.*',
                'patients.full_name as patient_name',
                'patients.date_of_birth',
                'patients.gender',
                'doctors.name as doctor_name',
                'doctors.id as doctor_id'
            )
            ->firstOrFail();

        $items = DB::table('lab_order_items')
            ->join('lab_tests_catalog', 'lab_order_items.lab_test_catalog_id', '=', 'lab_tests_catalog.id')
            ->where('lab_order_items.lab_order_id', $orderId)
            ->select(
                'lab_order_items.*',
                'lab_tests_catalog.name as test_name',
                'lab_tests_catalog.unit',
                'lab_tests_catalog.reference_range',
                'lab_tests_catalog.category'
            )
            ->get();

        // Update status to processing
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
            'results'              => 'required|array',
            'results.*.item_id'    => 'required|integer',
            'results.*.value'      => 'required|string|max:255',
            'results.*.is_abnormal'=> 'nullable|boolean',
            'results.*.is_critical'=> 'nullable|boolean',
            'results.*.remarks'    => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $orderId, $clinicId) {
            foreach ($validated['results'] as $result) {
                DB::table('lab_order_items')
                    ->where('id', $result['item_id'])
                    ->where('lab_order_id', $orderId)
                    ->update([
                        'result_value' => $result['value'],
                        'is_abnormal'  => !empty($result['is_abnormal']),
                        'remarks'      => $result['remarks'] ?? null,
                        'status'       => 'completed',
                        'updated_at'   => now(),
                    ]);
            }

            DB::table('lab_orders')->where('id', $orderId)->update([
                'status'       => 'completed',
                'completed_at' => now(),
                'updated_at'   => now(),
            ]);
        });

        Log::info('Lab results saved', ['order_id' => $orderId, 'by' => auth()->id()]);

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
            ->where('lab_orders.ordered_by', $doctorId)
            ->where('lab_orders.status', 'completed')
            ->select('lab_orders.*', 'patients.full_name as patient_name', 'patients.phone')
            ->orderByDesc('lab_orders.completed_at')
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
            ->join('users as doctors', 'lab_orders.ordered_by', '=', 'doctors.id')
            ->where('lab_orders.id', $orderId)
            ->where('lab_orders.clinic_id', $clinicId)
            ->select(
                'lab_orders.*',
                'patients.full_name as patient_name',
                'patients.date_of_birth',
                'patients.gender',
                'patients.phone',
                'doctors.name as doctor_name'
            )
            ->firstOrFail();

        $items = DB::table('lab_order_items')
            ->join('lab_tests_catalog', 'lab_order_items.lab_test_catalog_id', '=', 'lab_tests_catalog.id')
            ->where('lab_order_items.lab_order_id', $orderId)
            ->select('lab_order_items.*', 'lab_tests_catalog.name as test_name', 'lab_tests_catalog.unit', 'lab_tests_catalog.reference_range', 'lab_tests_catalog.category')
            ->get();

        $clinicName = auth()->user()->clinic->name ?? config('app.name');

        return view('lab.technician.report', compact('order', 'items', 'clinicName'));
    }
}
