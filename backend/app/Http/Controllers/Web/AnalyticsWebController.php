<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AnalyticsWebController extends Controller
{
    public function index(Request $request)
    {
        Log::info('AnalyticsWebController@index', ['user' => auth()->id()]);

        try {
            $clinicId = auth()->user()->clinic_id;
            $period = $request->get('period', 'month');

        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        // Revenue stats - use correct column names (total, paid, payment_status, payment_date)
        $revenue = [
            'total' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                ->where('payment_date', '>=', $startDate)
                ->sum('amount') ?? 0,
            'pending' => Invoice::where('clinic_id', $clinicId)
                ->where('payment_status', '!=', 'paid')
                ->selectRaw('SUM(total - paid) as pending_total')
                ->value('pending_total') ?? 0,
            'collected_today' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                ->whereDate('payment_date', today())
                ->sum('amount') ?? 0,
        ];

        // Daily revenue for chart
        $dailyRevenue = Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
            ->where('payment_date', '>=', $startDate)
            ->select(
                DB::raw('DATE(payment_date) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Appointment stats
        $appointments = [
            'total' => Appointment::where('clinic_id', $clinicId)
                ->where('scheduled_at', '>=', $startDate)
                ->count(),
            'completed' => Appointment::where('clinic_id', $clinicId)
                ->where('scheduled_at', '>=', $startDate)
                ->where('status', 'completed')
                ->count(),
            'noshow_rate' => $this->calculateNoShowRate($clinicId, $startDate),
        ];

        // Patient stats
        $patients = [
            'total' => Patient::where('clinic_id', $clinicId)->count(),
            'new' => Patient::where('clinic_id', $clinicId)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'returning' => Appointment::where('clinic_id', $clinicId)
                ->where('scheduled_at', '>=', $startDate)
                ->whereHas('patient', fn($q) => $q->where('created_at', '<', $startDate))
                ->distinct('patient_id')
                ->count('patient_id'),
        ];

        // Top services - safe query even if service_id is null
        $topServices = collect();
        try {
            $topServices = DB::table('appointments')
                ->leftJoin('appointment_services', 'appointments.service_id', '=', 'appointment_services.id')
                ->where('appointments.clinic_id', $clinicId)
                ->where('appointments.scheduled_at', '>=', $startDate)
                ->whereNotNull('appointment_services.id')
                ->select('appointment_services.name', DB::raw('COUNT(*) as count'))
                ->groupBy('appointment_services.name')
                ->orderByDesc('count')
                ->limit(5)
                ->get();
        } catch (\Throwable $e) {
            Log::warning('Top services query failed', ['error' => $e->getMessage()]);
        }

        // GST summary
        $gstSummary = [
            'taxable' => Invoice::where('clinic_id', $clinicId)
                ->where('created_at', '>=', $startDate)
                ->sum('subtotal'),
            'cgst' => Invoice::where('clinic_id', $clinicId)
                ->where('created_at', '>=', $startDate)
                ->sum('cgst_amount'),
            'sgst' => Invoice::where('clinic_id', $clinicId)
                ->where('created_at', '>=', $startDate)
                ->sum('sgst_amount'),
        ];

            Log::info('AnalyticsWebController@index success');

            return view('analytics.index', compact(
                'revenue', 'dailyRevenue', 'appointments', 'patients',
                'topServices', 'gstSummary', 'period'
            ));
        } catch (\Throwable $e) {
            Log::error('AnalyticsWebController@index error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }
    private function calculateNoShowRate($clinicId, $startDate): float
    {
        $total = Appointment::where('clinic_id', $clinicId)
            ->where('scheduled_at', '>=', $startDate)
            ->whereIn('status', ['completed', 'no_show'])
            ->count();

        if ($total === 0) return 0;

        $noShows = Appointment::where('clinic_id', $clinicId)
            ->where('scheduled_at', '>=', $startDate)
            ->where('status', 'no_show')
            ->count();

        return round(($noShows / $total) * 100, 1);
    }
}
