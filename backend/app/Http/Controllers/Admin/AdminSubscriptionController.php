<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminSubscriptionController extends Controller
{
    public function index()
    {
        Log::info('AdminSubscriptionController@index');

        // Plan pricing configuration
        $plans = [
            'trial' => ['name' => 'Free Trial', 'price' => 0, 'yearly' => 0, 'doctors' => 1, 'patients' => 50],
            'solo' => ['name' => 'Solo Practice', 'price' => 999, 'yearly' => 9999, 'doctors' => 1, 'patients' => -1],
            'small' => ['name' => 'Small Clinic', 'price' => 2499, 'yearly' => 24999, 'doctors' => 3, 'patients' => -1],
            'group' => ['name' => 'Group Practice', 'price' => 4999, 'yearly' => 49999, 'doctors' => 10, 'patients' => -1],
            'enterprise' => ['name' => 'Enterprise', 'price' => 0, 'yearly' => 0, 'doctors' => -1, 'patients' => -1],
        ];

        // Get clinic counts by plan
        $clinicsByPlan = Clinic::selectRaw('plan, COUNT(*) as count')
            ->groupBy('plan')
            ->pluck('count', 'plan')
            ->toArray();

        // Calculate MRR (Monthly Recurring Revenue)
        $mrr = 0;
        foreach ($clinicsByPlan as $plan => $count) {
            if (isset($plans[$plan]) && $plans[$plan]['price'] > 0) {
                $mrr += $plans[$plan]['price'] * $count;
            }
        }

        // ARR = MRR * 12
        $arr = $mrr * 12;

        // Active trials
        $activeTrials = Clinic::where('plan', 'trial')
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('trial_ends_at')
                  ->orWhere('trial_ends_at', '>', now());
            })
            ->count();

        // Get all paid clinics (non-trial)
        $paidClinics = Clinic::with('owner')
            ->whereIn('plan', ['solo', 'small', 'group', 'enterprise'])
            ->where('is_active', true)
            ->latest()
            ->get();

        // Get trials expiring soon (next 7 days)
        $expiringTrials = Clinic::with('owner')
            ->where('plan', 'trial')
            ->where('is_active', true)
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
            ->orderBy('trial_ends_at')
            ->get();

        // Get expired trials
        $expiredTrials = Clinic::with('owner')
            ->where('plan', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->latest('trial_ends_at')
            ->limit(10)
            ->get();

        return view('admin.subscriptions.index', compact(
            'plans',
            'clinicsByPlan',
            'mrr',
            'arr',
            'activeTrials',
            'paidClinics',
            'expiringTrials',
            'expiredTrials'
        ));
    }

    public function updatePlan(Request $request, Clinic $clinic)
    {
        Log::info('AdminSubscriptionController@updatePlan', ['clinic_id' => $clinic->id]);

        $validated = $request->validate([
            'plan' => 'required|in:trial,solo,small,group,enterprise',
        ]);

        try {
            $oldPlan = $clinic->plan;
            
            $clinic->update([
                'plan' => $validated['plan'],
                'trial_ends_at' => $validated['plan'] === 'trial' ? now()->addDays(30) : null,
            ]);

            Log::info('Clinic plan updated', [
                'clinic_id' => $clinic->id,
                'old_plan' => $oldPlan,
                'new_plan' => $validated['plan'],
            ]);

            return back()->with('success', "Plan updated to {$validated['plan']} for {$clinic->name}");

        } catch (\Throwable $e) {
            Log::error('Plan update failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to update plan: ' . $e->getMessage());
        }
    }

    public function extendTrial(Request $request, Clinic $clinic)
    {
        Log::info('AdminSubscriptionController@extendTrial', ['clinic_id' => $clinic->id]);

        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        try {
            $currentEnd = $clinic->trial_ends_at ?? now();
            $newEnd = $currentEnd->addDays($validated['days']);

            $clinic->update([
                'plan' => 'trial',
                'trial_ends_at' => $newEnd,
            ]);

            Log::info('Trial extended', [
                'clinic_id' => $clinic->id,
                'extended_by' => $validated['days'],
                'new_end' => $newEnd,
            ]);

            return back()->with('success', "Trial extended by {$validated['days']} days for {$clinic->name}");

        } catch (\Throwable $e) {
            Log::error('Trial extension failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to extend trial: ' . $e->getMessage());
        }
    }
}
