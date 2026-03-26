<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    public function index()
    {
        Log::info('AdminDashboardController@index', ['user' => auth()->id()]);

        try {
            // Overall stats
            $stats = [
                'total_clinics' => Clinic::count(),
                'active_clinics' => Clinic::where('is_active', true)->count(),
                'trial_clinics' => Clinic::whereNotNull('trial_ends_at')
                    ->where('trial_ends_at', '>', now())
                    ->count(),
                'expired_trials' => Clinic::whereNotNull('trial_ends_at')
                    ->where('trial_ends_at', '<=', now())
                    ->where('plan', 'trial')
                    ->count(),
                'total_users' => User::count(),
                'total_patients' => Patient::count(),
                'total_appointments' => Appointment::count(),
                'this_month_revenue' => Invoice::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->where('payment_status', 'paid')
                    ->sum('total') ?? 0,
            ];

            // Clinics by plan
            $clinicsByPlan = Clinic::select('plan', DB::raw('COUNT(*) as count'))
                ->groupBy('plan')
                ->pluck('count', 'plan')
                ->toArray();

            // Recent signups (last 10 clinics)
            $recentClinics = Clinic::with('owner')
                ->latest()
                ->limit(10)
                ->get();

            // Monthly signups (last 6 months)
            $monthlySignups = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $count = Clinic::whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->count();
                $monthlySignups[] = [
                    'month' => $month->format('M Y'),
                    'count' => $count,
                ];
            }

            // Clinics expiring soon (trial ending in 7 days)
            $expiringSoon = Clinic::whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '>', now())
                ->where('trial_ends_at', '<=', now()->addDays(7))
                ->with('owner')
                ->get();

            // Platform revenue (from subscription payments - placeholder for now)
            $platformRevenue = [
                'total' => 0, // Will be calculated from subscriptions table
                'this_month' => 0,
                'last_month' => 0,
            ];

            Log::info('AdminDashboardController stats loaded', $stats);

            return view('admin.dashboard', compact(
                'stats', 'clinicsByPlan', 'recentClinics', 
                'monthlySignups', 'expiringSoon', 'platformRevenue'
            ));
            
        } catch (\Throwable $e) {
            Log::error('AdminDashboardController error', ['error' => $e->getMessage()]);
            
            return view('admin.dashboard', [
                'stats' => [
                    'total_clinics' => 0, 'active_clinics' => 0,
                    'trial_clinics' => 0, 'expired_trials' => 0,
                    'total_users' => 0, 'total_patients' => 0,
                    'total_appointments' => 0, 'this_month_revenue' => 0,
                ],
                'clinicsByPlan' => [],
                'recentClinics' => collect(),
                'monthlySignups' => [],
                'expiringSoon' => collect(),
                'platformRevenue' => ['total' => 0, 'this_month' => 0, 'last_month' => 0],
                'error' => $e->getMessage(),
            ]);
        }
    }
}
