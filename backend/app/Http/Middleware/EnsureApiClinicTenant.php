<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API v2+: require authenticated user with an active clinic (tenant).
 * Sets request attribute "clinic" (App\Models\Clinic) for downstream middleware/controllers.
 */
class EnsureApiClinicTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            Log::debug('EnsureApiClinicTenant: no user');

            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->clinic_id === null) {
            Log::warning('EnsureApiClinicTenant: user missing clinic_id', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Clinic context required. This token is not scoped to a clinic.',
            ], 403);
        }

        $clinic = Clinic::query()->find($user->clinic_id);

        if ($clinic === null) {
            Log::warning('EnsureApiClinicTenant: clinic not found', ['clinic_id' => $user->clinic_id]);

            return response()->json(['message' => 'Clinic not found.'], 403);
        }

        if (! $clinic->is_active) {
            Log::info('EnsureApiClinicTenant: clinic inactive', ['clinic_id' => $clinic->id]);

            return response()->json(['message' => 'Clinic account is inactive.'], 403);
        }

        $request->attributes->set('clinic', $clinic);
        Log::debug('EnsureApiClinicTenant: clinic bound', ['clinic_id' => $clinic->id]);

        return $next($request);
    }
}
