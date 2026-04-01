<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\V2\BootstrapController;
use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Laravel Blade shell for ClinicOS v2 (same app as legacy dashboard; no React).
 */
class AppShellController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        Log::info('AppShellController@index', ['user_id' => $user?->id]);

        if ($user === null || $user->clinic_id === null) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'The v2 workspace requires a clinic account.');
        }

        $clinic = Clinic::query()->find($user->clinic_id);

        if ($clinic === null || ! $clinic->is_active) {
            Log::warning('AppShellController: clinic missing or inactive', ['clinic_id' => $user->clinic_id]);

            return redirect()
                ->route('dashboard')
                ->with('error', 'Your clinic is not active for the v2 workspace.');
        }

        $bootstrap = BootstrapController::buildPayload($user, $clinic);
        $enabledSet = array_flip($bootstrap['modules']['enabled_keys'] ?? []);

        return view('app-v2.index', [
            'bootstrap' => $bootstrap,
            'bootstrapJson' => json_encode($bootstrap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'enabledSet' => $enabledSet,
            'clinic' => $clinic,
        ]);
    }
}
