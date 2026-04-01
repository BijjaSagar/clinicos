<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use App\Support\ClinicProductModules;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API v2+: 403 if the clinic does not have the given product module enabled
 * (config/clinic_modules.php + clinics.settings.enabled_product_modules).
 *
 * Usage: Route::middleware(['clinic.module:clinical_emr'])->...
 */
class EnsureClinicProductModule
{
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $clinic = $request->attributes->get('clinic');

        if (! $clinic instanceof Clinic) {
            Log::error('EnsureClinicProductModule: clinic attribute missing — use clinic.tenant first', [
                'module' => $moduleKey,
            ]);

            return response()->json(['message' => 'Server configuration error.'], 500);
        }

        if (! ClinicProductModules::clinicHasModule($clinic, $moduleKey)) {
            Log::info('EnsureClinicProductModule: module not enabled for clinic', [
                'clinic_id' => $clinic->id,
                'module' => $moduleKey,
            ]);

            return response()->json([
                'message' => 'This feature is not enabled for your clinic.',
                'module' => $moduleKey,
            ], 403);
        }

        return $next($request);
    }
}
