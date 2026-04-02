<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LabDepartment;
use App\Models\LabTestCatalog;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LabController extends Controller
{
    /**
     * Internal HIMS Lab dashboard (route: laboratory.index)
     */
    public function dashboard()
    {
        $clinicId = auth()->user()->clinic_id;

        $stats = [
            'total_tests'   => LabTestCatalog::where('clinic_id', $clinicId)->count(),
            'active_tests'  => LabTestCatalog::where('clinic_id', $clinicId)->where('is_active', true)->count(),
            // For lab/index.blade.php stats
            'orders_today'    => DB::table('lab_orders')
                ->where('clinic_id', $clinicId)
                ->whereDate('created_at', today())
                ->count(),
            'pending_results' => DB::table('lab_orders')
                ->where('clinic_id', $clinicId)
                ->whereIn('status', ['pending', 'ordered', 'sample_collected', 'processing'])
                ->count(),
            'results_received' => DB::table('lab_orders')
                ->where('clinic_id', $clinicId)
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];

        $recentOrders = DB::table('lab_orders')
            ->leftJoin('patients', 'lab_orders.patient_id', '=', 'patients.id')
            ->where('lab_orders.clinic_id', $clinicId)
            ->orderByDesc('lab_orders.created_at')
            ->limit(10)
            ->select('lab_orders.*', 'patients.name as patient_name')
            ->get();

        $patients = \App\Models\Patient::where('clinic_id', $clinicId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('lab.index', compact('stats', 'recentOrders', 'patients'));
    }

    /**
     * Test catalog (route: laboratory.catalog)
     */
    public function catalog(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;

        $query = LabTestCatalog::with('department')
            ->where('clinic_id', $clinicId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('test_name', 'like', "%{$s}%")
                  ->orWhere('test_code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('department', fn ($q) => $q->where('name', $request->category));
        }

        $tests = $query->orderBy('test_name')->paginate(20)->withQueryString();

        $categories = LabDepartment::where('clinic_id', $clinicId)->pluck('name');

        return view('lab.catalog', compact('tests', 'categories'));
    }

    /**
     * Store a new test in the catalog (route: laboratory.catalog.store)
     * Accepts the old form fields (name, code, category, …) and maps to actual DB columns.
     */
    public function storeTest(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'code'             => 'nullable|string|max:50',
            'category'         => 'nullable|string|max:100',
            'sample_type'      => 'nullable|string|max:100',
            'unit'             => 'nullable|string|max:50',
            'price'            => 'required|numeric|min:0',
            'turnaround_hours' => 'nullable|integer|min:1',
            'reference_range'  => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        $clinicId = auth()->user()->clinic_id;

        // Find or create department by category name
        $deptName   = $validated['category'] ?? 'General';
        $department = LabDepartment::firstOrCreate(
            ['clinic_id' => $clinicId, 'name' => $deptName],
            ['is_active' => true]
        );

        // Map sample_type to allowed enum values
        $sampleTypeMap = [
            'blood'  => 'blood', 'urine' => 'urine', 'stool' => 'stool',
            'swab'   => 'swab',  'fluid' => 'fluid', 'tissue' => 'tissue',
            'sputum' => 'sputum',
        ];
        $sampleTypeRaw = strtolower($validated['sample_type'] ?? 'blood');
        $sampleType    = $sampleTypeMap[$sampleTypeRaw] ?? 'other';

        LabTestCatalog::create([
            'clinic_id'     => $clinicId,
            'department_id' => $department->id,
            'test_name'     => $validated['name'],
            'test_code'     => $validated['code'] ?? null,
            'test_type'     => 'single',
            'price'         => $validated['price'],
            'sample_type'   => $sampleType,
            'unit'          => $validated['unit'] ?? null,
            'tat_hours'     => $validated['turnaround_hours'] ?? 24,
            'is_active'     => true,
        ]);

        Log::info('Lab test added to catalog', ['clinic_id' => $clinicId, 'test_name' => $validated['name']]);

        return redirect()->route('laboratory.catalog')->with('success', 'Test "' . $validated['name'] . '" added to catalog.');
    }

    /**
     * Lab orders list (route: laboratory.orders)
     */
    public function orders(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;

        $query = DB::table('lab_orders')
            ->leftJoin('patients', 'lab_orders.patient_id', '=', 'patients.id')
            ->where('lab_orders.clinic_id', $clinicId)
            ->select('lab_orders.*', 'patients.name as patient_name', 'patients.phone as patient_phone');

        if ($request->filled('status')) {
            $query->where('lab_orders.status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('lab_orders.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('lab_orders.created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('patients.name', 'like', "%{$s}%")
                  ->orWhere('lab_orders.order_number', 'like', "%{$s}%");
            });
        }

        $orders = $query->orderByDesc('lab_orders.created_at')->paginate(20)->withQueryString();

        $patients = Patient::where('clinic_id', $clinicId)->orderBy('name')->get(['id', 'name', 'phone']);
        $tests    = LabTestCatalog::where('clinic_id', $clinicId)->where('is_active', true)->orderBy('test_name')->get();

        // Stats for the view
        $stats = [
            'pending'         => DB::table('lab_orders')->where('clinic_id', $clinicId)->where('status', 'pending')->count(),
            'in_progress'     => DB::table('lab_orders')->where('clinic_id', $clinicId)->whereIn('status', ['sample_collected', 'processing'])->count(),
            'completed_today' => DB::table('lab_orders')->where('clinic_id', $clinicId)->where('status', 'completed')->whereDate('updated_at', today())->count(),
            'total_month'     => DB::table('lab_orders')->where('clinic_id', $clinicId)->whereMonth('created_at', now()->month)->count(),
        ];

        return view('lab.orders', compact('orders', 'patients', 'tests', 'stats'));
    }

    /**
     * Store a new lab order (route: laboratory.orders.store)
     */
    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'tests'      => 'required|array|min:1',
            'tests.*'    => 'exists:lab_tests_catalog,id',
            'notes'      => 'nullable|string',
            'priority'   => 'nullable|in:routine,urgent,stat',
        ]);

        $clinicId = auth()->user()->clinic_id;

        DB::transaction(function () use ($validated, $clinicId) {
            $orderNumber = 'LAB-' . strtoupper(uniqid());

            // Calculate total for external-lab compatibility fields
            $totalAmount = LabTestCatalog::whereIn('id', $validated['tests'])->sum('price');

            $orderId = DB::table('lab_orders')->insertGetId([
                'clinic_id'    => $clinicId,
                'patient_id'   => $validated['patient_id'],
                'created_by'   => auth()->id(),
                'status'       => 'pending',
                // External-lab columns (nullable after fix migration)
                'provider'         => 'internal',
                'provider_name'    => 'Internal Lab',
                'tests'            => json_encode($validated['tests']),
                'total_amount'     => $totalAmount ?? 0,
                // HIMS columns
                'order_number'     => $orderNumber,
                'order_date'       => now()->toDateString(),
                'priority'         => $validated['priority'] ?? 'routine',
                'clinical_notes'   => $validated['notes'] ?? null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            if (DB::getSchemaBuilder()->hasTable('lab_order_items')) {
                foreach ($validated['tests'] as $testId) {
                    $test = LabTestCatalog::find($testId);
                    DB::table('lab_order_items')->insert([
                        'order_id'  => $orderId,   // correct column name
                        'test_id'   => $testId,    // correct column name
                        'status'    => 'pending',
                        'price'     => $test?->price ?? 0,
                        'discount'  => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('laboratory.orders')->with('success', 'Lab order created successfully');
    }

    /**
     * Result entry form (route: laboratory.result-entry)
     */
    public function resultEntry(Request $request, $orderId)
    {
        $clinicId = auth()->user()->clinic_id;

        $order = DB::table('lab_orders')
            ->leftJoin('patients', 'lab_orders.patient_id', '=', 'patients.id')
            ->where('lab_orders.id', $orderId)
            ->where('lab_orders.clinic_id', $clinicId)
            ->select('lab_orders.*', 'patients.name as patient_name')
            ->first();

        abort_if(!$order, 404);

        // Get items via lab_order_items (HIMS schema) or fallback to tests JSON
        if (DB::getSchemaBuilder()->hasTable('lab_order_items')) {
            $items = DB::table('lab_order_items')
                ->join('lab_tests_catalog', 'lab_order_items.test_id', '=', 'lab_tests_catalog.id')
                ->where('lab_order_items.order_id', $orderId)
                ->select(
                    'lab_order_items.*',
                    'lab_tests_catalog.test_name',
                    'lab_tests_catalog.unit',
                )
                ->get()
                ->map(function ($item) {
                    $item->result_value   = $item->result_value   ?? null;
                    $item->is_abnormal    = $item->is_abnormal    ?? false;
                    $item->remarks        = $item->remarks        ?? null;
                    $item->reference_range = null;
                    return $item;
                });
        } else {
            $items = collect();
        }

        return view('lab.result-entry', compact('order', 'items'));
    }

    /**
     * Save results (route: laboratory.save-result)
     */
    public function saveResult(Request $request, $orderId)
    {
        $validated = $request->validate([
            'results'                => 'required|array',
            'results.*.item_id'      => 'required|integer',
            'results.*.value'        => 'required|string',
            'results.*.is_abnormal'  => 'nullable|boolean',
            'results.*.remarks'      => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $orderId) {
            foreach ($validated['results'] as $result) {
                // Update the order item status
                DB::table('lab_order_items')
                    ->where('id', $result['item_id'])
                    ->where('order_id', $orderId)  // correct column name
                    ->update([
                        'status'     => 'completed',
                        'updated_at' => now(),
                    ]);

                // Get test_id for the lab_results insert
                $item = DB::table('lab_order_items')->where('id', $result['item_id'])->first();

                if ($item) {
                    // Insert into lab_results table
                    $clinicId = auth()->user()->clinic_id;
                    $existingResult = DB::table('lab_results')
                        ->where('order_item_id', $item->id)
                        ->first();

                    $resultData = [
                        'clinic_id'      => $clinicId,
                        'order_item_id'  => $item->id,
                        'test_id'        => $item->test_id,
                        'value'          => $result['value'],
                        'is_abnormal'    => $result['is_abnormal'] ?? false,
                        'is_critical'    => false,
                        'notes'          => $result['remarks'] ?? null,
                        'result_date'    => now(),
                        'updated_at'     => now(),
                    ];

                    if ($existingResult) {
                        DB::table('lab_results')
                            ->where('id', $existingResult->id)
                            ->update($resultData);
                    } else {
                        DB::table('lab_results')->insert(
                            array_merge($resultData, ['created_at' => now()])
                        );
                    }
                }
            }

            // Mark order as completed
            DB::table('lab_orders')->where('id', $orderId)->update([
                'status'     => 'completed',
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('laboratory.orders')->with('success', 'Results saved successfully');
    }
}
