<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Dashboard KPIs
     */
    public function dashboard(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching dashboard analytics', ['clinic_id' => $clinicId]);

        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // Today's stats
        $todayAppointments = Appointment::forClinic($clinicId)->today()->count();
        $todayCompleted = Appointment::forClinic($clinicId)->today()->byStatus('completed')->count();
        $todayRevenue = Payment::forClinic($clinicId)->forDate($today)->sum('amount');

        // This month stats
        $monthPatients = Patient::forClinic($clinicId)
            ->where('created_at', '>=', $thisMonth)
            ->count();
        $monthRevenue = Invoice::forClinic($clinicId)
            ->where('invoice_date', '>=', $thisMonth)
            ->sum('paid');
        $monthAppointments = Appointment::forClinic($clinicId)
            ->where('scheduled_at', '>=', $thisMonth)
            ->count();

        // Outstanding
        $outstanding = Invoice::forClinic($clinicId)
            ->outstanding()
            ->sum(DB::raw('total - paid'));

        // Comparison with last month
        $lastMonthRevenue = Invoice::forClinic($clinicId)
            ->whereBetween('invoice_date', [$lastMonth, $thisMonth])
            ->sum('paid');

        $revenueGrowth = $lastMonthRevenue > 0
            ? (($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        $dashboard = [
            'today' => [
                'appointments' => $todayAppointments,
                'completed' => $todayCompleted,
                'revenue' => $todayRevenue,
                'pending' => $todayAppointments - $todayCompleted,
            ],
            'this_month' => [
                'new_patients' => $monthPatients,
                'revenue' => $monthRevenue,
                'appointments' => $monthAppointments,
                'revenue_growth_pct' => round($revenueGrowth, 1),
            ],
            'outstanding' => [
                'total' => $outstanding,
                'count' => Invoice::forClinic($clinicId)->outstanding()->count(),
            ],
        ];

        Log::info('Dashboard analytics generated', ['clinic_id' => $clinicId]);

        return response()->json([
            'dashboard' => $dashboard,
        ]);
    }

    /**
     * Revenue analytics
     */
    public function revenue(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching revenue analytics', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : now()->subDays(30);
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : now();
        $groupBy = $validated['group_by'] ?? 'day';

        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
        };

        $revenue = Invoice::forClinic($clinicId)
            ->whereBetween('invoice_date', [$from, $to])
            ->select([
                DB::raw("DATE_FORMAT(invoice_date, '{$dateFormat}') as period"),
                DB::raw('SUM(total) as total_billed'),
                DB::raw('SUM(paid) as total_collected'),
                DB::raw('COUNT(*) as invoice_count'),
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $summary = [
            'total_billed' => $revenue->sum('total_billed'),
            'total_collected' => $revenue->sum('total_collected'),
            'collection_rate' => $revenue->sum('total_billed') > 0
                ? ($revenue->sum('total_collected') / $revenue->sum('total_billed')) * 100
                : 0,
            'invoice_count' => $revenue->sum('invoice_count'),
        ];

        Log::info('Revenue analytics generated', [
            'clinic_id' => $clinicId,
            'periods' => $revenue->count()
        ]);

        return response()->json([
            'revenue' => $revenue,
            'summary' => $summary,
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }

    /**
     * Appointment analytics
     */
    public function appointments(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching appointment analytics', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : now()->subDays(30);
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : now();

        $appointments = Appointment::forClinic($clinicId)
            ->whereBetween('scheduled_at', [$from, $to])
            ->get();

        $byStatus = $appointments->groupBy('status')->map->count();
        $byType = $appointments->groupBy('appointment_type')->map->count();
        $bySource = $appointments->groupBy('booking_source')->map->count();
        $bySpecialty = $appointments->groupBy('specialty')->map->count();

        // Daily distribution
        $byDay = $appointments->groupBy(fn($a) => Carbon::parse($a->scheduled_at)->format('Y-m-d'))
            ->map->count()
            ->sortKeys();

        // Time slot analysis
        $byHour = $appointments->groupBy(fn($a) => Carbon::parse($a->scheduled_at)->format('H'))
            ->map->count()
            ->sortKeys();

        $noShowRate = $appointments->count() > 0
            ? ($byStatus['no_show'] ?? 0) / $appointments->count() * 100
            : 0;

        Log::info('Appointment analytics generated', ['clinic_id' => $clinicId]);

        return response()->json([
            'total' => $appointments->count(),
            'by_status' => $byStatus,
            'by_type' => $byType,
            'by_source' => $bySource,
            'by_specialty' => $bySpecialty,
            'by_day' => $byDay,
            'by_hour' => $byHour,
            'no_show_rate' => round($noShowRate, 1),
        ]);
    }

    /**
     * Patient analytics
     */
    public function patients(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching patient analytics', ['clinic_id' => $clinicId]);

        $totalPatients = Patient::forClinic($clinicId)->count();
        $newThisMonth = Patient::forClinic($clinicId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        // Retention analysis
        $withMultipleVisits = Patient::forClinic($clinicId)
            ->where('visit_count', '>', 1)
            ->count();
        $retentionRate = $totalPatients > 0
            ? ($withMultipleVisits / $totalPatients) * 100
            : 0;

        // Patients needing follow-up
        $needingFollowup = Patient::forClinic($clinicId)
            ->needingFollowup()
            ->count();

        // ABHA adoption
        $withAbha = Patient::forClinic($clinicId)
            ->withAbha()
            ->count();
        $abhaRate = $totalPatients > 0
            ? ($withAbha / $totalPatients) * 100
            : 0;

        // By source
        $bySource = Patient::forClinic($clinicId)
            ->select('source', DB::raw('COUNT(*) as count'))
            ->groupBy('source')
            ->pluck('count', 'source');

        // Growth trend (last 6 months)
        $growth = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = Patient::forClinic($clinicId)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $growth[$month->format('Y-m')] = $count;
        }

        Log::info('Patient analytics generated', ['clinic_id' => $clinicId]);

        return response()->json([
            'total' => $totalPatients,
            'new_this_month' => $newThisMonth,
            'retention_rate' => round($retentionRate, 1),
            'needing_followup' => $needingFollowup,
            'abha_adoption_rate' => round($abhaRate, 1),
            'by_source' => $bySource,
            'growth_trend' => $growth,
        ]);
    }

    /**
     * Doctor productivity
     */
    public function doctors(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching doctor analytics', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : now()->subDays(30);
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : now();

        $doctors = User::forClinic($clinicId)->doctors()->active()->get();

        $productivity = $doctors->map(function ($doctor) use ($from, $to) {
            $appointments = Appointment::forDoctor($doctor->id)
                ->whereBetween('scheduled_at', [$from, $to])
                ->get();

            $visits = Visit::forDoctor($doctor->id)
                ->whereBetween('created_at', [$from, $to])
                ->get();

            $revenue = Invoice::whereIn('visit_id', $visits->pluck('id'))
                ->sum('total');

            return [
                'doctor_id' => $doctor->id,
                'name' => $doctor->name,
                'specialty' => $doctor->specialty,
                'appointments' => $appointments->count(),
                'completed' => $appointments->where('status', 'completed')->count(),
                'no_shows' => $appointments->where('status', 'no_show')->count(),
                'visits' => $visits->count(),
                'finalised' => $visits->where('status', 'finalised')->count(),
                'revenue' => $revenue,
            ];
        });

        Log::info('Doctor analytics generated', ['clinic_id' => $clinicId]);

        return response()->json([
            'doctors' => $productivity,
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }

    /**
     * Specialty-specific KPIs
     */
    public function specialty(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching specialty analytics', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'specialty' => 'nullable|string|max:50',
        ]);

        $specialty = $validated['specialty'] ?? null;

        $query = Visit::forClinic($clinicId);
        if ($specialty) {
            $query->bySpecialty($specialty);
        }

        $visits = $query->with('scales')->get();

        // Group by specialty
        $bySpecialty = $visits->groupBy('specialty')->map(function ($specialtyVisits, $spec) {
            $scales = $specialtyVisits->flatMap->scales;
            
            return [
                'visit_count' => $specialtyVisits->count(),
                'finalised' => $specialtyVisits->where('status', 'finalised')->count(),
                'scales_recorded' => $scales->count(),
                'avg_scales' => $specialtyVisits->count() > 0
                    ? round($scales->count() / $specialtyVisits->count(), 1)
                    : 0,
            ];
        });

        // Scale score trends (for dermatology - PASI, etc.)
        $scalesTrends = [];
        if ($specialty === 'dermatology') {
            $scalesTrends = [
                'PASI' => $visits->flatMap->scales
                    ->where('scale_name', 'PASI')
                    ->avg('score'),
                'IGA' => $visits->flatMap->scales
                    ->where('scale_name', 'IGA')
                    ->avg('score'),
                'DLQI' => $visits->flatMap->scales
                    ->where('scale_name', 'DLQI')
                    ->avg('score'),
            ];
        }

        Log::info('Specialty analytics generated', ['clinic_id' => $clinicId]);

        return response()->json([
            'by_specialty' => $bySpecialty,
            'scale_trends' => $scalesTrends,
        ]);
    }
}
