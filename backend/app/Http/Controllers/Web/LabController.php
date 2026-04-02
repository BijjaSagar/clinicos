<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LabTestCatalog;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LabController extends Controller
{
    public function dashboard()
    {
        $clinicId = auth()->user()->clinic_id;
        $stats = [
            'total_tests' => LabTestCatalog::where('clinic_id', $clinicId)->count(),
            'active_tests' => LabTestCatalog::where('clinic_id', $clinicId)->where('is_active', true)->count(),
        ];
        return view('lab.index', compact('stats'));
    }

    public function catalog()
    {
        $clinicId = auth()->user()->clinic_id;
        $tests = LabTestCatalog::where('clinic_id', $clinicId)->orderBy('name')->paginate(20);
        return view('lab.catalog', compact('tests'));
    }

    public function storeTest(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'turnaround_hours' => 'nullable|integer|min:1',
            'sample_type' => 'nullable|string|max:100',
            'reference_range' => 'nullable|string',
            'unit' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);
        $validated['clinic_id'] = auth()->user()->clinic_id;
        $validated['is_active'] = true;
        LabTestCatalog::create($validated);
        return redirect()->route('lab.catalog')->with('success', 'Test added successfully');
    }

    public function orders(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $orders = DB::table('lab_orders')
            ->join('patients', 'lab_orders.patient_id', '=', 'patients.id')
            ->where('lab_orders.clinic_id', $clinicId)
            ->select('lab_orders.*', 'patients.name as patient_name', 'patients.phone as patient_phone')
            ->orderByDesc('lab_orders.created_at')
            ->paginate(20);
        $patients = Patient::where('clinic_id', $clinicId)->orderBy('name')->get(['id','name','phone']);
        $tests = LabTestCatalog::where('clinic_id', $clinicId)->where('is_active', true)->orderBy('name')->get();
        return view('lab.orders', compact('orders', 'patients', 'tests'));
    }

    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'tests' => 'required|array|min:1',
            'tests.*' => 'exists:lab_tests_catalog,id',
            'notes' => 'nullable|string',
            'priority' => 'in:routine,urgent,stat',
        ]);
        $clinicId = auth()->user()->clinic_id;
        DB::transaction(function () use ($validated, $clinicId) {
            $orderId = DB::table('lab_orders')->insertGetId([
                'clinic_id' => $clinicId,
                'patient_id' => $validated['patient_id'],
                'ordered_by' => auth()->id(),
                'status' => 'pending',
                'priority' => $validated['priority'] ?? 'routine',
                'notes' => $validated['notes'] ?? null,
                'order_number' => 'LAB-' . strtoupper(uniqid()),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($validated['tests'] as $testId) {
                $test = LabTestCatalog::find($testId);
                DB::table('lab_order_items')->insert([
                    'lab_order_id' => $orderId,
                    'lab_test_catalog_id' => $testId,
                    'status' => 'pending',
                    'price' => $test?->price ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
        return redirect()->route('lab.orders')->with('success', 'Lab order created successfully');
    }

    public function resultEntry(Request $request, $orderId)
    {
        $clinicId = auth()->user()->clinic_id;
        $order = DB::table('lab_orders')
            ->join('patients', 'lab_orders.patient_id', '=', 'patients.id')
            ->where('lab_orders.id', $orderId)
            ->where('lab_orders.clinic_id', $clinicId)
            ->select('lab_orders.*', 'patients.name as patient_name')
            ->firstOrFail();
        $items = DB::table('lab_order_items')
            ->join('lab_tests_catalog', 'lab_order_items.lab_test_catalog_id', '=', 'lab_tests_catalog.id')
            ->where('lab_order_items.lab_order_id', $orderId)
            ->select('lab_order_items.*', 'lab_tests_catalog.name as test_name', 'lab_tests_catalog.unit', 'lab_tests_catalog.reference_range')
            ->get();
        return view('lab.result-entry', compact('order', 'items'));
    }

    public function saveResult(Request $request, $orderId)
    {
        $validated = $request->validate([
            'results' => 'required|array',
            'results.*.item_id' => 'required|integer',
            'results.*.value' => 'required|string',
            'results.*.is_abnormal' => 'nullable|boolean',
            'results.*.remarks' => 'nullable|string',
        ]);
        DB::transaction(function () use ($validated, $orderId) {
            foreach ($validated['results'] as $result) {
                DB::table('lab_order_items')
                    ->where('id', $result['item_id'])
                    ->where('lab_order_id', $orderId)
                    ->update([
                        'result_value' => $result['value'],
                        'is_abnormal' => $result['is_abnormal'] ?? false,
                        'remarks' => $result['remarks'] ?? null,
                        'status' => 'completed',
                        'updated_at' => now(),
                    ]);
            }
            DB::table('lab_orders')->where('id', $orderId)->update([
                'status' => 'completed',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
        });
        return redirect()->route('lab.orders')->with('success', 'Results saved successfully');
    }
}
