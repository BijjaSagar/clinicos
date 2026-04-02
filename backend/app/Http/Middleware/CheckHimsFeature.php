<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckHimsFeature
{
    /**
     * Check if the authenticated user's clinic has a HIMS feature enabled.
     *
     * Usage: middleware('hims:ipd') or middleware('hims:pharmacy_inventory')
     * Feature keys are defined in config/hims_expansion.php
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super admins bypass
        if ($user->clinic_id === null && $user->role === 'super_admin') {
            return $next($request);
        }

        $clinic = $user->clinic;
        if (!$clinic) {
            Log::warning('CheckHimsFeature: User has no clinic', ['user_id' => $user->id]);
            return redirect()->route('dashboard')->with('error', 'No clinic associated with your account.');
        }

        // Check facility type — clinics don't get hospital features by default
        $himsFeatures = $clinic->hims_features ?? [];

        // If hims_features is empty/null, check if facility is hospital type (allow all)
        if (empty($himsFeatures) && in_array($clinic->facility_type, ['hospital', 'multispecialty_hospital'])) {
            return $next($request);
        }

        // Check the specific feature flag
        if (!empty($himsFeatures[$feature])) {
            return $next($request);
        }

        Log::info('CheckHimsFeature: Feature not enabled', [
            'user_id' => $user->id,
            'clinic_id' => $clinic->id,
            'feature' => $feature,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'This module is not enabled for your facility. Contact your administrator to upgrade.',
            ], 403);
        }

        return redirect()->route('dashboard')->with('error', "The {$feature} module is not enabled for your facility. Please contact your administrator or upgrade your plan.");
    }
}
