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
        $total_medicines = PharmacyItem::where('clinic_id', $clinicId)
            ->active()
            ->count();

        $low_stock_count = PharmacyItem::where('clinic_id', $clinicId)
            ->active()
            ->lowStock()
            ->count();

        $dispensed_today = PharmacyDispensing::where('clinic_id', $clinicId)
            ->whereDate('dispensed_at', today())
            ->count();

        $monthly_revenue = PharmacyDispensing::where('clinic_id', $clinicId)
            ->whereYear('dispensed_at', now()->year)
            ->whereMonth('dispensed_at', now()->month)
            ->sum('total_amount');

        $stats = compact('total_medicines', 'low_stock_count', 'dispensed_today', 'monthly_revenue');

        // ── Recent dispensing records (last 20) — flat query for view ─────────
        $recentDispensing = DB::table('pharmacy_dispensing')
            ->leftJoin('patients', 'pharmacy_dispensing.patient_id', '=', 'patients.id')
            ->where('pharmacy_dispensing.clinic_id', $clinicId)
            ->orderByDesc('pharmacy_dispensing.dispensed_at')
            ->limit(20)
            ->select(
                'pharmacy_dispensing.*',
                'patients.name as patient_name'
            )
            ->get()
            ->each(function ($row) {
                $row->items_count = DB::table('pharmacy_dispensing_items')
                    ->where('dispensing_id', $row->id)
                    ->count();
            });

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
            ->whereDate('dispensed_at', today())
            ->count();

        $dispensedToday = $pendingCount;

        $lowStockItems = PharmacyItem::where('clinic_id', $clinicId)
            ->active()
            ->lowStock()
            ->orderBy('name')
            ->take(10)
            ->get();

        $nearExpiryItems = PharmacyStock::where('clinic_id', $clinicId)
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now())
            ->where('quantity_available', '>', 0)
            ->with('item')
            ->take(10)
            ->get();

        $recentDispensing = DB::table('pharmacy_dispensing')
            ->leftJoin('patients', 'pharmacy_dispensing.patient_id', '=', 'patients.id')
            ->leftJoin('users', 'pharmacy_dispensing.dispensed_by', '=', 'users.id')
            ->where('pharmacy_dispensing.clinic_id', $clinicId)
            ->orderByDesc('pharmacy_dispensing.dispensed_at')
            ->take(10)
            ->select(
                'pharmacy_dispensing.*',
                'patients.name as patient_name',
                'users.name as dispensed_by_name'
            )
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

        if ($request->boolean('low_stock') || $request->input('stock_status') === 'low-stock') {
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

    // ── Dispense Form (GET) ──────────────────────────────────────────────────

    public function dispensingForm()
    {
        $clinicId = auth()->user()->clinic_id;
        $patients = Patient::where('clinic_id', $clinicId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'age_years', 'sex']);
        return view('pharmacy.dispensing', compact('patients'));
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

        $validated['clinic_id']     = auth()->user()->clinic_id;
        $validated['is_active']     = true;
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
        // The inventory form uses: pharmacy_item_id, quantity, batch_number,
        // expiry_date, purchase_price, supplier_name — map to DB column names.
        $validated = $request->validate([
            'pharmacy_item_id' => 'required|integer|exists:pharmacy_items,id',
            'quantity'         => 'required|integer|min:1',
            'batch_number'     => 'nullable|string|max:100',
            'expiry_date'      => 'nullable|date',
            'purchase_price'   => 'nullable|numeric|min:0',
            'supplier_name'    => 'nullable|string|max:255',
        ]);

        $stock = PharmacyStock::create([
            'clinic_id'          => auth()->user()->clinic_id,
            'item_id'            => $validated['pharmacy_item_id'],
            'batch_number'       => $validated['batch_number']  ?? null,
            'expiry_date'        => $validated['expiry_date']   ?? null,
            'quantity_in'        => $validated['quantity'],
            'quantity_out'       => 0,
            'quantity_available' => $validated['quantity'],
            'purchase_rate'      => $validated['purchase_price'] ?? 0,
            'mrp'                => $validated['purchase_price'] ?? 0,
        ]);

        Log::info('PharmacyController@stockIn created', ['stock_id' => $stock->id]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Stock received.', 'stock' => $stock]);
        }

        return redirect()->route('pharmacy.inventory')->with('success', 'Stock added successfully.');
    }

    // ── Dispense (POST) ──────────────────────────────────────────────────────

    public function dispense(Request $request)
    {
        // POST — process dispensing
        $validated = $request->validate([
            'patient_id'           => 'nullable|integer|exists:patients,id',
            'payment_mode'         => 'required|in:cash,card,upi,credit',
            'discount'             => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string|max:500',
            'items'                => 'required|array|min:1',
            'items.*.item_id'      => 'required|integer|exists:pharmacy_items,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.instructions' => 'nullable|string|max:255',
        ]);

        $clinicId = auth()->user()->clinic_id;
        $discountAmount = (float) ($validated['discount'] ?? 0);

        DB::beginTransaction();
        try {
            $subtotal  = 0;
            $gstTotal  = 0;
            $lineItems = [];

            foreach ($validated['items'] as $line) {
                $item   = PharmacyItem::findOrFail($line['item_id']);
                $needed = (int) $line['quantity'];

                // FIFO: oldest expiry first, non-expired batches only
                $batches = PharmacyStock::where('clinic_id', $clinicId)
                    ->where('item_id', $item->id)
                    ->where('quantity_available', '>', 0)
                    ->nonExpired()
                    ->orderBy('expiry_date')
                    ->get();

                $remaining   = $needed;
                $usedBatches = [];

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    $take          = min($batch->quantity_available, $remaining);
                    $usedBatches[] = ['batch' => $batch, 'qty' => $take];
                    $remaining    -= $take;
                }

                if ($remaining > 0) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for \"{$item->name}\". Only " . ($needed - $remaining) . ' units available.',
                    ], 422);
                }

                // Deduct stock
                $firstBatch = $usedBatches[0]['batch'];
                foreach ($usedBatches as $entry) {
                    $batch = $entry['batch'];
                    $take  = $entry['qty'];
                    $batch->quantity_out       += $take;
                    $batch->quantity_available -= $take;
                    $batch->save();
                }

                $unitPrice = (float) $item->selling_price;
                $gstRate   = (float) $item->gst_rate;
                $lineBase  = round($unitPrice * $needed, 2);
                $lineGst   = round($lineBase * $gstRate / 100, 2);

                $subtotal += $lineBase;
                $gstTotal += $lineGst;

                $lineItems[] = [
                    'item_id'      => $item->id,
                    'batch_number' => $firstBatch->batch_number,
                    'quantity'     => $needed,
                    'unit_price'   => $unitPrice,        // DB column: unit_price
                    'gst_amount'   => $lineGst,          // DB column: gst_amount
                    'total_price'  => $lineBase + $lineGst, // DB column: total_price
                    'instructions' => $line['instructions'] ?? null,
                ];
            }

            $totalAmount = round($subtotal + $gstTotal - $discountAmount, 2);
            $dispensingNumber = 'RX-' . strtoupper(uniqid());

            $dispensing = PharmacyDispensing::create([
                'clinic_id'         => $clinicId,
                'patient_id'        => $validated['patient_id'] ?? null,
                'dispensing_number' => $dispensingNumber,
                'dispensed_by'      => auth()->id(),
                'payment_mode'      => $validated['payment_mode'],
                'total_amount'      => $totalAmount,      // DB column: total_amount
                'discount_amount'   => $discountAmount,   // DB column: discount_amount
                'paid_amount'       => $totalAmount,      // DB column: paid_amount
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
                'total'             => $totalAmount,
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
            ->latest('dispensed_at');

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('dispensed_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('dispensed_at', '<=', $request->to);
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

        $today = now()->toDateString();
        $soon  = now()->addDays(90)->toDateString();

        return view('pharmacy.stock-report', compact('items', 'today', 'soon'));
    }

    // ── Expiry Alert (JSON) ───────────────────────────────────────────────────

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

    // ── Drug Search (JSON) ────────────────────────────────────────────────────

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

        $items->each(function ($item) {
            $item->stock_quantity = $item->stock_quantity;
        });

        return response()->json($items);
    }

    // ── Patient Search (JSON) ─────────────────────────────────────────────────

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
