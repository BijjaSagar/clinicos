<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentWebController extends Controller
{
    public function index(Request $request)
    {
        Log::info('PaymentWebController@index', ['user' => auth()->id()]);

        try {
            $clinicId = auth()->user()->clinic_id;
            
            $query = Payment::with(['patient', 'invoice', 'recordedBy'])
                ->whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                ->orderByDesc('payment_date');

            // Filter by payment method
            if ($request->filled('method')) {
                $query->where('payment_method', $request->method);
            }

            // Filter by date range
            if ($request->filled('from')) {
                $query->whereDate('payment_date', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('payment_date', '<=', $request->to);
            }

            $payments = $query->paginate(20);

            // Stats
            $today = now()->toDateString();
            $weekStart = now()->startOfWeek()->toDateString();
            $monthStart = now()->startOfMonth()->toDateString();

            $stats = [
                'today' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('payment_date', $today)
                    ->sum('amount') ?? 0,
                'today_count' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('payment_date', $today)
                    ->count(),
                'week' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('payment_date', '>=', $weekStart)
                    ->sum('amount') ?? 0,
                'week_count' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('payment_date', '>=', $weekStart)
                    ->count(),
                'month' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('payment_date', '>=', $monthStart)
                    ->sum('amount') ?? 0,
                'month_count' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('payment_date', '>=', $monthStart)
                    ->count(),
                'pending' => Invoice::where('clinic_id', $clinicId)
                    ->whereIn('payment_status', ['pending', 'partial'])
                    ->selectRaw('SUM(total - paid) as pending_total')
                    ->value('pending_total') ?? 0,
                'pending_count' => Invoice::where('clinic_id', $clinicId)
                    ->whereIn('payment_status', ['pending', 'partial'])
                    ->count(),
            ];

            // Payment method breakdown for this month
            $methodBreakdown = Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                ->whereDate('payment_date', '>=', $monthStart)
                ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('payment_method')
                ->get()
                ->keyBy('payment_method');

            Log::info('PaymentWebController@index success', ['payments_count' => $payments->count()]);

            return view('payments.index', compact('payments', 'stats', 'methodBreakdown'));
            
        } catch (\Throwable $e) {
            Log::error('PaymentWebController@index error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            // Return view with empty data
            return view('payments.index', [
                'payments' => collect(),
                'stats' => [
                    'today' => 0, 'today_count' => 0,
                    'week' => 0, 'week_count' => 0,
                    'month' => 0, 'month_count' => 0,
                    'pending' => 0, 'pending_count' => 0,
                ],
                'methodBreakdown' => collect(),
                'error' => 'Could not load payments: ' . $e->getMessage()
            ]);
        }
    }
}
