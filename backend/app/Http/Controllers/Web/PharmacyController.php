<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PharmacyDispensing;
use App\Models\PharmacyDispensingItem;
use App\Models\PharmacyItem;
use App\Models\PharmacyStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PharmacyController extends Controller
{
    // ── Dashboard ────────────────────────────────────────────────────────────

    public function index()
    {
        Log::info('PharmacyController@index', ['user' => auth()->id()]);

        $clinicId = auth()->user()->clinic_id;

        // ── Stats ────────────────────────────────────────────────────────────
        $totalMedicines = PharmacyItem::where('clinic_id', $clinicId)->active()->count();

        $lowStockCount = PharmacyItem::where('clinic_id', $clinicId)->active()->lowStock()->count();

        $dispensedToday = PharmacyDispensing::where('clinic_id', $clinicId)
            ->whereDate('created_at', today())->count();

        $monthlyRevenue = PharmacyDispensing::where('clinic_id', $clinicId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $stats = [
            'total_medicines'  => $totalMedicines,
            'low_stock_count'  => $lowStockCount,
            'dispensed_today'  => $dispensedToday,
            'monthly_revenue'  => number_format($monthlyRevenue, 0),
            // keep old keys too for any other views
            'itemsInStock'     => $totalMedicines,
            'lowStockCount'    => $lowStockCount,
            'expiringSoon'     => PharmacyStock::where('clinic_id', $clinicId)->where('quantity_available', '>', 0)->where('expiry_date', '>=', now()->toDateString())->where('expiry_date', '<=', now()->addDays(30)->toDateString())->count(),
            'todaysSalesAmount'=> PharmacyDispensing::where('clinic_id', $clinicId)->whereDate('created_at', today())->sum('total'),
        ];

        // ── Recent dispensing records — flat query so view can access ->patient_name etc ──
        $recentDispensing = DB::table('pharmacy_dispensing')
            ->leftJoin('patients', 'pharmacy_dispensing.patient_id', '=', 'patients.id')
            ->where('pharmacy_dispensing.clinic_id', $clinicId)
            ->select(
                'pharmacy_dispensing.*',
                'patients.name as patient_name',
                DB::raw('(SELECT COUNT(*) FROM pharmacy_dispensing_items WHERE dispensing_id = pharmacy_dispensing.id) as items_count'),
                'pharmacy_dispensing.total as total_amount'
            )
            ->orderByDesc('pharmacy_dispensing.created_at')
            ->limit(20)
            ->get();

        // ── Low stock alerts ──────────────────────────────────────────────────
        $lowStockItems = PharmacyItem::where('clinic_id', $clinicId)
            ->active()
            ->lowStock()
            ->limit(10)
            ->get();

        return view('pharmacy.index', compact('stats', 'recentDispensing', 'lowStockItems'));
    }

    // ── Pharmacist Portal ──────────────────────────────────────────────────

    public function pharmacistPortal()
    {
        $clinicId = auth()->user()->clinic_id;

        $pendingCount = PharmacyDispensing::where('clinic_id', $clinicId)
            ->where('status', 'pending')
            ->count();

        $dispensedToday = PharmacyDispensing::where('clinic_id', $clinicId)
            ->whereDate('created_at', today())
            ->where('status', 'dispensed')
            ->count();

        $lowStockItems = PharmacyItem::where('clinic_id', $clinicId)
            ->active()
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->orderBy('current_stock')
            ->take(10)
            ->get();

        $nearExpiryItems = PharmacyStock::where('clinic_id', $clinicId)
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now())
            ->where('quantity', '>', 0)
            ->with('pharmacyItem')
            ->take(10)
            ->get();

        $recentDispensing = PharmacyDispensing::where('clinic_id', $clinicId)
            ->with(['patient', 'dispensedBy'])
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('pharmacy.pharmacist-portal', compact(
            'pendingCount', 'dispensedToday', 'lowStockItems', 'nearExpiryItems', 'recentDispensing'
        ));
    }

    // ── Inventory ────────────────────────────────────────────────────────────

    public function inventory(Request $request)
    {
        Log::info('PharmacyController@inventory', ['user' => auth()->id()]);

        $clinicId = auth()->user()->clinic_id;

        $query = PharmacyItem::with(['stocks' => function ($q) {
            $q->orderBy('expiry_date');
        }])
        ->where('clinic_id', $clinicId)
        ->active();

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        if ($request->boolean('expiring_soon')) {
            $query->whereHas('stocks', function ($q) {
                $q->where('quantity_available', '>', 0)
                  ->where('expiry_date', '>=', now()->toDateString())
                  ->where('expiry_date', '<=', now()->addDays(90)->toDateString());
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('name')->paginate(30)->withQueryString();

        return view('pharmacy.inventory', compact('items'));
    }

    // ── Add Item ─────────────────────────────────────────────────────────────

    public function addItem(Request $request)
    {
        if ($request->isMethod('GET')) {
            return view('pharmacy.add-item');
        }

        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'generic_name'       => 'nullable|string|max:255',
            'category_id'        => 'nullable|integer',
            'hsn_code'           => 'nullable|string|max:50',
            'unit'               => 'required|string|max:50',
            'pack_size'          => 'nullable|string|max:50',
            'manufacturer'       => 'nullable|string|max:255',
            'schedule'           => 'nullable|string|max:10',
            'is_controlled'      => 'boolean',
            'gst_rate'           => 'required|numeric|min:0|max:100',
            'mrp'                => 'required|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0',
            'reorder_level'      => 'nullable|integer|min:0',
            'reorder_qty'        => 'nullable|integer|min:0',
            'storage_conditions' => 'nullable|string|max:255',
        ]);

        $validated['clinic_id']    = auth()->user()->clinic_id;
        $validated['is_active']    = true;
        $validated['is_controlled'] = $request->boolean('is_controlled');

        $item = PharmacyItem::create($validated);

        Log::info('PharmacyController@addItem created', ['item_id' => $item->id]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'item' => $item], 201);
        }

        return redirect()->route('pharmacy.inventory')
            ->with('success', "Drug \"{$item->name}\" added to inventory.");
    }

    // ── Stock In ─────────────────────────────────────────────────────────────

    public function stockIn(Request $request)
    {
        $validated = $request->validate([
            'item_id'       => 'required|integer|exists:pharmacy_items,id',
            'batch_number'  => 'required|string|max:100',
            'expiry_date'   => 'required|date|after:today',
            'quantity_in'   => 'required|integer|min:1',
            'purchase_rate' => 'required|numeric|min:0',
            'mrp'           => 'required|numeric|min:0',
            'supplier_id'   => 'nullable|integer',
            'grn_id'        => 'nullable|integer',
        ]);

        $validated['clinic_id']          = auth()->user()->clinic_id;
        $validated['quantity_out']        = 0;
        $validated['quantity_available']  = $validated['quantity_in'];

        $stock = PharmacyStock::create($validated);

        Log::info('PharmacyController@stockIn created', ['stock_id' => $stock->id]);

        return response()->json([
            'success' => true,
            'message' => 'Stock received successfully.',
            'stock'   => $stock,
        ]);
    }

    // ── Dispensing Form (GET alias) ───────────────────────────────────────────

    public function dispensingForm()
    {
        $clinicId = auth()->user()->clinic_id;
        $patients = Patient::where('clinic_id', $clinicId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'age_years', 'sex']);
        return view('pharmacy.dispensing', compact('patients'));
    }

    // ── Dispense ─────────────────────────────────────────────────────────────

    public function dispense(Request $request)
    {
        if ($request->isMethod('GET')) {
            $clinicId = auth()->user()->clinic_id;
            $patients = Patient::where('clinic_id', $clinicId)
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'age_years', 'sex']);
            return view('pharmacy.dispensing', compact('patients'));
        }

        // POST — process dispensing
        $validated = $request->validate([
            'patient_id'       => 'nullable|integer|exists:patients,id',
            'payment_mode'     => 'required|in:cash,card,upi',
            'discount'         => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string|max:500',
            'items'            => 'required|array|min:1',
            'items.*.item_id'  => 'required|integer|exists:pharmacy_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.instructions' => 'nullable|string|max:255',
        ]);

        $clinicId = auth()->user()->clinic_id;
        $discount = (float) ($validated['discount'] ?? 0);

        DB::beginTransaction();
        try {
            $subtotal  = 0;
            $gstTotal  = 0;
            $lineItems = [];

            foreach ($validated['items'] as $line) {
                $item     = PharmacyItem::findOrFail($line['item_id']);
                $needed   = (int) $line['quantity'];

                // FIFO: oldest expiry first, non-expired batches only
                $batches = PharmacyStock::where('clinic_id', $clinicId)
                    ->where('item_id', $item->id)
                    ->where('quantity_available', '>', 0)
                    ->nonExpired()
                    ->orderBy('expiry_date')
                    ->get();

                $remaining = $needed;
                $usedBatches = [];

                foreach ($batches as $batch) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $take = min($batch->quantity_available, $remaining);
                    $usedBatches[] = ['batch' => $batch, 'qty' => $take];
                    $remaining -= $take;
                }

                if ($remaining > 0) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for \"{$item->name}\". Only " . ($needed - $remaining) . ' units available.',
                    ], 422);
                }

                // Deduct stock and build line items
                $firstBatch = $usedBatches[0]['batch'];
                foreach ($usedBatches as $entry) {
                    /** @var PharmacyStock $batch */
                    $batch = $entry['batch'];
                    $take  = $entry['qty'];

                    $batch->quantity_out       += $take;
                    $batch->quantity_available -= $take;
                    $batch->save();
                }

                $unitPrice  = (float) $item->selling_price;
                $gstRate    = (float) $item->gst_rate;
                $lineTotal  = round($unitPrice * $needed, 2);
                $lineGst    = round($lineTotal * $gstRate / 100, 2);

                $subtotal += $lineTotal;
                $gstTotal += $lineGst;

                $lineItems[] = [
                    'item_id'       => $item->id,
                    'stock_id'      => $firstBatch->id,
                    'batch_number'  => $firstBatch->batch_number,
                    'expiry_date'   => $firstBatch->expiry_date,
                    'quantity'      => $needed,
                    'selling_price' => $unitPrice,
                    'gst_rate'      => $gstRate,
                    'gst_amount'    => $lineGst,
                    'total'         => $lineTotal + $lineGst,
                    'instructions'  => $line['instructions'] ?? null,
                ];
            }

            $total = round($subtotal + $gstTotal - $discount, 2);

            // Build dispensing number
            $dispensingNumber = 'RX-' . strtoupper(uniqid());

            $dispensing = PharmacyDispensing::create([
                'clinic_id'         => $clinicId,
                'patient_id'        => $validated['patient_id'] ?? null,
                'dispensing_number' => $dispensingNumber,
                'dispensed_by'      => auth()->id(),
                'payment_mode'      => $validated['payment_mode'],
                'subtotal'          => $subtotal,
                'gst_amount'        => $gstTotal,
                'discount'          => $discount,
                'total'             => $total,
                'notes'             => $validated['notes'] ?? null,
                'dispensed_at'      => now(),
            ]);

            foreach ($lineItems as $li) {
                $li['dispensing_id'] = $dispensing->id;
                PharmacyDispensingItem::create($li);
            }

            DB::commit();

            Log::info('PharmacyController@dispense created', [
                'dispensing_id'     => $dispensing->id,
                'dispensing_number' => $dispensingNumber,
            ]);

            return response()->json([
                'success'           => true,
                'dispensing_number' => $dispensingNumber,
                'total'             => $total,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PharmacyController@dispense error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Dispensing failed. Please try again.'], 500);
        }
    }

    // ── Dispensing History ────────────────────────────────────────────────────

    public function dispensingHistory(Request $request)
    {
        Log::info('PharmacyController@dispensingHistory', ['user' => auth()->id()]);

        $clinicId = auth()->user()->clinic_id;

        $query = PharmacyDispensing::with(['patient', 'items.item', 'dispensedBy'])
            ->where('clinic_id', $clinicId)
            ->latest();

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('dispensing_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $dispensings = $query->paginate(30)->withQueryString();

        return view('pharmacy.dispensing-history', compact('dispensings'));
    }

    // ── Stock Report ──────────────────────────────────────────────────────────

    public function stockReport()
    {
        Log::info('PharmacyController@stockReport', ['user' => auth()->id()]);

        $clinicId = auth()->user()->clinic_id;

        $items = PharmacyItem::with(['stocks' => function ($q) {
            $q->orderBy('expiry_date');
        }])
        ->where('clinic_id', $clinicId)
        ->active()
        ->orderBy('name')
        ->get();

        $today       = now()->toDateString();
        $soon        = now()->addDays(90)->toDateString();

        return view('pharmacy.stock-report', compact('items', 'today', 'soon'));
    }

    // ── Expiry Alert (JSON for dashboard widget) ──────────────────────────────

    public function expiryAlert()
    {
        $clinicId = auth()->user()->clinic_id;

        $batches = PharmacyStock::with('item:id,name,generic_name')
            ->where('clinic_id', $clinicId)
            ->where('quantity_available', '>', 0)
            ->where('expiry_date', '>=', now()->toDateString())
            ->where('expiry_date', '<=', now()->addDays(60)->toDateString())
            ->orderBy('expiry_date')
            ->get(['id', 'item_id', 'batch_number', 'expiry_date', 'quantity_available']);

        return response()->json([
            'count'   => $batches->count(),
            'batches' => $batches,
        ]);
    }

    // ── Drug Search (JSON for dispensing autocomplete) ────────────────────────

    public function searchDrugs(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $search   = $request->get('q', '');

        $items = PharmacyItem::where('clinic_id', $clinicId)
            ->active()
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%");
            })
            ->limit(15)
            ->get(['id', 'name', 'generic_name', 'selling_price', 'gst_rate', 'unit', 'schedule']);

        // Attach live stock qty
        $items->each(function ($item) {
            $item->stock_quantity = $item->stock_quantity;
        });

        return response()->json($items);
    }

    // ── Patient Search (JSON for dispensing autocomplete) ─────────────────────

    public function searchPatients(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $search   = $request->get('q', '');

        $patients = Patient::where('clinic_id', $clinicId)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'phone', 'age_years', 'sex']);

        return response()->json($patients);
    }
}
