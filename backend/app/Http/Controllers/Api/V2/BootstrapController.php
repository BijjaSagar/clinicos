<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Support\ClinicProductModules;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * JSON bootstrap for API clients + shared payload for Blade workspace v2.
 */
class BootstrapController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public static function buildPayload(?Authenticatable $user, ?Clinic $clinic): array
    {
        $definitions = config('clinic_modules.modules', []);
        $enabledKeys = $clinic
            ? ClinicProductModules::enabledModuleKeys($clinic)
            : [];

        Log::debug('BootstrapController::buildPayload', [
            'user_id' => $user?->getAuthIdentifier(),
            'clinic_id' => $clinic?->id,
            'enabled_module_count' => count($enabledKeys),
        ]);

        return [
            'api' => [
                'version' => config('clinicos_api_v2.version', '2.0.0'),
                'base_path' => '/api/v2',
                'legacy_api' => '/api/v1',
            ],
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'specialty' => $user->specialty,
                'clinic_id' => $user->clinic_id,
            ] : null,
            'clinic' => $clinic ? [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'slug' => $clinic->slug,
                'plan' => $clinic->plan,
                'specialties' => $clinic->specialties,
                'city' => $clinic->city,
                'state' => $clinic->state,
            ] : null,
            'modules' => [
                'enabled_keys' => $enabledKeys,
                'definitions' => $definitions,
            ],
        ];
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $clinic = $request->attributes->get('clinic');

        Log::info('Api\\V2\\BootstrapController@show', [
            'user_id' => $user?->id,
            'clinic_id' => $clinic?->id,
        ]);

        $clinicModel = $clinic instanceof Clinic ? $clinic : null;

        return response()->json(self::buildPayload($user, $clinicModel));
    }
}
