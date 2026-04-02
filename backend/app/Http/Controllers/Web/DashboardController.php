<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Redirect to setup wizard if clinic hasn't completed setup
        $clinic = auth()->user()->clinic;
        if ($clinic && !($clinic->settings['setup_completed'] ?? false) && auth()->user()->role === 'owner') {
            return redirect()->route('setup-wizard.index');
        }

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
            // Today's appointments (models — stats use this collection)
            $todayAppointments = Appointment::with(['patient'])
                ->where('clinic_id', $clinicId)
                ->whereDate('scheduled_at', $today)
                ->orderBy('scheduled_at')
                ->get();

            Log::info('DashboardController: today appointments loaded', [
                'count' => $todayAppointments->count(),
                'ids' => $todayAppointments->pluck('id')->all(),
            ]);

        } catch (\Throwable $e) {
            Log::error('Error loading appointments', ['error' => $e->getMessage()]);
            $todayAppointments = collect();
        }

        try {
            // KPI stats - using correct column names from invoices table
            $stats = [
                'today_patients' => $todayAppointments->whereIn('status', ['completed', 'in_consultation', 'checked_in'])->count(),
                'revenue' => Invoice::where('clinic_id', $clinicId)
                    ->whereDate('created_at', $today)
                    ->where('payment_status', 'paid')
                    ->sum('total') ?? 0,
                'pending_dues' => Invoice::where('clinic_id', $clinicId)
                    ->whereIn('payment_status', ['pending', 'partial'])
                    ->selectRaw('SUM(total - paid) as pending')
                    ->value('pending') ?? 0,
                'queue_count' => $todayAppointments->whereIn('status', ['checked_in', 'confirmed', 'booked'])->count(),
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

        // Blade "Today's Schedule" expects array rows (time, name, status, …), not Eloquent models.
        $appointments = $todayAppointments->map(fn (Appointment $apt) => $this->mapAppointmentForDashboard($apt))->values();

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

    /**
     * @return array{time: string, name: string, initials: string, gradient: string, type: string, status: string, token: mixed, id: int, url: string}
     */
    private function mapAppointmentForDashboard(Appointment $apt): array
    {
        $name = $apt->patient?->name ?? 'Patient';
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = count($parts) >= 2
            ? strtoupper(substr((string) $parts[0], 0, 1).substr((string) end($parts), 0, 1))
            : strtoupper(substr($name, 0, 2));

        $statusUi = match ($apt->status) {
            'completed' => 'done',
            'checked_in' => 'waiting',
            'in_consultation' => 'in-consultation',
            'no_show' => 'no-show',
            'cancelled' => 'no-show',
            default => str_replace('_', '-', $apt->status),
        };

        $type = ucfirst(str_replace('_', ' ', $apt->appointment_type ?? 'consultation'));
        if ($apt->booking_source === 'online_booking') {
            $type .= ' · Web booking';
        }

        Log::debug('DashboardController: mapAppointmentForDashboard', [
            'appointment_id' => $apt->id,
            'status_ui' => $statusUi,
        ]);

        return [
            'time' => $apt->scheduled_at->format('H:i'),
            'name' => $name,
            'initials' => $initials ?: 'P',
            'gradient' => '#1447e6,#0891b2',
            'type' => $type,
            'status' => $statusUi,
            'token' => $apt->token_number,
            'id' => $apt->id,
            'url' => route('appointments.show', $apt),
        ];
    }
}
