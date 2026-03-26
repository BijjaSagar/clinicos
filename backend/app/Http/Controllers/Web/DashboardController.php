<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        Log::info('DashboardController@index called');

        $user = auth()->user();
        $clinicId = $user->clinic_id ?? null;
        $today = today();

        // If no clinic, show empty dashboard
        if (!$clinicId) {
            Log::warning('User has no clinic_id', ['user_id' => $user->id]);
            return view('dashboard.index', [
                'appointments' => collect(),
                'stats' => [
                    'today_patients' => 0,
                    'revenue' => 0,
                    'pending_dues' => 0,
                    'queue_count' => 0,
                    'month_revenue' => 0,
                ],
                'whatsappMessages' => collect(),
                'revenueChart' => collect(),
            ]);
        }

        try {
            // Today's appointments
            $appointments = Appointment::with(['patient'])
                ->where('clinic_id', $clinicId)
                ->whereDate('scheduled_at', $today)
                ->orderBy('scheduled_at')
                ->get();

            Log::info('Appointments loaded', ['count' => $appointments->count()]);

        } catch (\Throwable $e) {
            Log::error('Error loading appointments', ['error' => $e->getMessage()]);
            $appointments = collect();
        }

        try {
            // KPI stats - using correct column names from invoices table
            $stats = [
                'today_patients' => $appointments->whereIn('status', ['completed', 'in_consultation', 'checked_in'])->count(),
                'revenue' => Invoice::where('clinic_id', $clinicId)
                    ->whereDate('created_at', $today)
                    ->where('payment_status', 'paid')
                    ->sum('total') ?? 0,
                'pending_dues' => Invoice::where('clinic_id', $clinicId)
                    ->whereIn('payment_status', ['pending', 'partial'])
                    ->selectRaw('SUM(total - paid) as pending')
                    ->value('pending') ?? 0,
                'queue_count' => $appointments->whereIn('status', ['checked_in', 'confirmed', 'booked'])->count(),
                'month_revenue' => Invoice::where('clinic_id', $clinicId)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->where('payment_status', 'paid')
                    ->sum('total') ?? 0,
            ];

            Log::info('Stats calculated', $stats);

        } catch (\Throwable $e) {
            Log::error('Error calculating stats', ['error' => $e->getMessage()]);
            $stats = [
                'today_patients' => 0,
                'revenue' => 0,
                'pending_dues' => 0,
                'queue_count' => 0,
                'month_revenue' => 0,
            ];
        }

        try {
            // Recent WhatsApp messages
            $whatsappMessages = WhatsappMessage::where('clinic_id', $clinicId)
                ->with('patient')
                ->latest()
                ->limit(5)
                ->get();
        } catch (\Throwable $e) {
            Log::error('Error loading WhatsApp messages', ['error' => $e->getMessage()]);
            $whatsappMessages = collect();
        }

        try {
            // Revenue last 7 days (for chart) - using correct column names
            $revenueChart = collect(range(6, 0))->map(function ($daysAgo) use ($clinicId) {
                $date = now()->subDays($daysAgo);

                return [
                    'date' => $date->format('d M'),
                    'amount' => Invoice::where('clinic_id', $clinicId)
                        ->whereDate('created_at', $date)
                        ->where('payment_status', 'paid')
                        ->sum('total') ?? 0,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('Error loading revenue chart', ['error' => $e->getMessage()]);
            $revenueChart = collect();
        }

        return view('dashboard.index', compact('appointments', 'stats', 'whatsappMessages', 'revenueChart'));
    }
}
