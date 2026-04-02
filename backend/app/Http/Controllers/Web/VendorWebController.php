<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use App\Models\VendorLab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VendorWebController extends Controller
{
    public function index(Request $request)
    {
        Log::info('VendorWebController@index', ['user' => auth()->id()]);

        try {
            $query = LabOrder::with(['patient', 'clinic', 'tests'])
                ->latest();

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $orders = $query->paginate(20);

            // Use correct status values from migration enum
            $stats = [
                'new_today' => LabOrder::whereDate('created_at', today())
                    ->where('status', 'new')->count(),
                'processing' => LabOrder::whereIn('status', ['accepted', 'sample_collected', 'processing'])->count(),
                'ready' => LabOrder::where('status', 'ready')->count(),
                'total_month' => LabOrder::whereMonth('created_at', now()->month)->count(),
            ];

            // Get partner clinics - handle if relationship doesn't exist
            $partnerClinics = collect();
            try {
                $partnerClinics = VendorLab::with('clinic')
                    ->limit(5)
                    ->get();
            } catch (\Throwable $e) {
                Log::warning('Could not load partner clinics', ['error' => $e->getMessage()]);
            }

            Log::info('VendorWebController@index success', ['orders_count' => $orders->count()]);

            return view('lab-orders.index', compact('orders', 'stats', 'partnerClinics'));
        } catch (\Throwable $e) {
            Log::error('VendorWebController@index error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    public function acceptOrder(LabOrder $order)
    {
        Log::info('VendorWebController@acceptOrder', ['order' => $order->id]);

        $order->update([
            'status' => 'processing',
            'accepted_at' => now(),
        ]);

        return back()->with('success', 'Order accepted successfully');
    }

    public function uploadResult(Request $request, LabOrder $order)
    {
        Log::info('VendorWebController@uploadResult', ['order' => $order->id]);

        $validated = $request->validate([
            'result_file' => 'required|file|mimes:pdf|max:10240',
            'notes' => 'nullable|string|max:500',
        ]);

        // Store file and update order
        if ($request->hasFile('result_file')) {
            $path = $request->file('result_file')->store('lab-results', 'public');
            $order->update([
                'result_file' => $path,
                'result_notes' => $validated['notes'] ?? null,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        return back()->with('success', 'Result uploaded successfully');
    }
}
