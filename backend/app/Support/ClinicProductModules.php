<?php

namespace App\Support;

use App\Models\Clinic;
use Illuminate\Support\Facades\Log;

final class ClinicProductModules
{
    /**
     * All valid module keys from config.
     *
     * @return list<string>
     */
    public static function validModuleKeys(): array
    {
        return array_keys(config('clinic_modules.modules', []));
    }

    /**
     * Enabled modules for this clinic. Missing / invalid settings = all modules (legacy behaviour).
     *
     * @return list<string>
     */
    public static function enabledModuleKeys(Clinic $clinic): array
    {
        $all = self::validModuleKeys();
        if ($all === []) {
            return [];
        }

        try {
            $stored = data_get($clinic->settings, 'enabled_product_modules');
        } catch (\Throwable $e) {
            Log::warning('ClinicProductModules: could not read settings', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage(),
            ]);

            return $all;
        }

        if (! is_array($stored) || $stored === []) {
            return $all;
        }

        $intersect = array_values(array_intersect($stored, $all));

        return $intersect === [] ? $all : $intersect;
    }

    public static function clinicHasModule(Clinic $clinic, string $moduleKey): bool
    {
        return in_array($moduleKey, self::enabledModuleKeys($clinic), true);
    }

    /**
     * Whether a sidebar nav item (by its "key") should show for this clinic.
     */
    public static function navItemVisible(Clinic $clinic, string $navKey): bool
    {
        $map = config('clinic_modules.nav_to_module', []);
        $moduleId = $map[$navKey] ?? null;

        if ($moduleId === null || $moduleId === '') {
            return true;
        }

        return self::clinicHasModule($clinic, $moduleId);
    }

    /**
     * Merge enabled_product_modules into clinic settings array.
     *
     * @param  list<string>  $enabledKeys
     * @return array<string, mixed>
     */
    public static function mergeEnabledIntoSettings(?array $existingSettings, array $enabledKeys): array
    {
        $settings = is_array($existingSettings) ? $existingSettings : [];
        $valid = self::validModuleKeys();
        $clean = array_values(array_intersect($enabledKeys, $valid));

        if ($clean === []) {
            $clean = $valid;
        }

        $settings['enabled_product_modules'] = $clean;
        Log::info('ClinicProductModules: merged enabled_product_modules', [
            'count' => count($clean),
        ]);

        return $settings;
    }
}
