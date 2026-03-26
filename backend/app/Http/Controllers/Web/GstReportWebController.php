<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GstReportWebController extends Controller
{
    public function index(Request $request)
    {
        Log::info('GstReportWebController@index', ['user' => auth()->id()]);

        try {
            $clinicId = auth()->user()->clinic_id;
            $clinic = auth()->user()->clinic;
            
            // Default to current month
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            
            // Get all invoices for the selected month
            $invoices = Invoice::with(['patient', 'items'])
                ->where('clinic_id', $clinicId)
                ->whereMonth('invoice_date', $month)
                ->whereYear('invoice_date', $year)
                ->orderBy('invoice_date')
                ->get();
            
            // Calculate GST summaries
            $totalTaxable = $invoices->sum('subtotal') - $invoices->sum('discount_amount');
            $totalCgst = $invoices->sum('cgst_amount');
            $totalSgst = $invoices->sum('sgst_amount');
            $totalIgst = $invoices->sum('igst_amount');
            $totalGst = $totalCgst + $totalSgst + $totalIgst;
            $totalInvoiceValue = $invoices->sum('total');
            
            // GST by rate breakdown
            $gstByRate = InvoiceItem::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId)
                    ->whereMonth('invoice_date', $month)
                    ->whereYear('invoice_date', $year))
                ->select(
                    'gst_rate',
                    DB::raw('SUM(taxable_amount) as taxable_total'),
                    DB::raw('SUM(cgst_amount) as cgst_total'),
                    DB::raw('SUM(sgst_amount) as sgst_total'),
                    DB::raw('COUNT(DISTINCT invoice_id) as invoice_count')
                )
                ->groupBy('gst_rate')
                ->orderBy('gst_rate')
                ->get();
            
            // Summary stats
            $stats = [
                'total_invoices' => $invoices->count(),
                'paid_invoices' => $invoices->where('payment_status', 'paid')->count(),
                'total_taxable' => $totalTaxable,
                'total_cgst' => $totalCgst,
                'total_sgst' => $totalSgst,
                'total_igst' => $totalIgst,
                'total_gst' => $totalGst,
                'total_value' => $totalInvoiceValue,
            ];
            
            // B2C (Business to Consumer) summary - typically all clinic invoices
            $b2cSummary = [
                'count' => $invoices->count(),
                'taxable' => $totalTaxable,
                'cgst' => $totalCgst,
                'sgst' => $totalSgst,
            ];
            
            // Monthly comparison (last 6 months)
            $monthlyTrend = [];
            for ($i = 5; $i >= 0; $i--) {
                $trendMonth = now()->subMonths($i);
                $monthlyData = Invoice::where('clinic_id', $clinicId)
                    ->whereMonth('invoice_date', $trendMonth->month)
                    ->whereYear('invoice_date', $trendMonth->year)
                    ->selectRaw('SUM(cgst_amount + sgst_amount + igst_amount) as gst, SUM(total) as total, COUNT(*) as count')
                    ->first();
                
                $monthlyTrend[] = [
                    'month' => $trendMonth->format('M Y'),
                    'gst' => $monthlyData->gst ?? 0,
                    'total' => $monthlyData->total ?? 0,
                    'count' => $monthlyData->count ?? 0,
                ];
            }

            Log::info('GstReportWebController@index success', ['invoices_count' => $invoices->count()]);

            return view('gst-reports.index', compact(
                'invoices', 'stats', 'gstByRate', 'b2cSummary', 
                'monthlyTrend', 'month', 'year', 'clinic'
            ));
            
        } catch (\Throwable $e) {
            Log::error('GstReportWebController@index error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            return view('gst-reports.index', [
                'invoices' => collect(),
                'stats' => [
                    'total_invoices' => 0, 'paid_invoices' => 0,
                    'total_taxable' => 0, 'total_cgst' => 0, 'total_sgst' => 0,
                    'total_igst' => 0, 'total_gst' => 0, 'total_value' => 0,
                ],
                'gstByRate' => collect(),
                'b2cSummary' => ['count' => 0, 'taxable' => 0, 'cgst' => 0, 'sgst' => 0],
                'monthlyTrend' => [],
                'month' => now()->month,
                'year' => now()->year,
                'clinic' => auth()->user()->clinic,
                'error' => 'Could not load GST reports: ' . $e->getMessage()
            ]);
        }
    }
}
