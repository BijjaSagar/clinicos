<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminWhatsAppSettingsController extends Controller
{
    private function getSettings(): array
    {
        $rows = DB::table('platform_settings')
            ->whereIn('key', [
                'wa_phone_number_id',
                'wa_waba_id',
                'wa_access_token',
                'wa_app_secret',
                'wa_verify_token',
            ])
            ->pluck('value', 'key')
            ->toArray();

        return array_merge([
            'wa_phone_number_id' => config('services.whatsapp.phone_number_id', ''),
            'wa_waba_id'         => '',
            'wa_access_token'    => config('services.whatsapp.token', ''),
            'wa_app_secret'      => '',
            'wa_verify_token'    => config('services.whatsapp.verify_token', 'clinicos_webhook_verify'),
        ], $rows);
    }

    public function index()
    {
        $settings = $this->getSettings();

        $webhookUrl = url('/api/v1/whatsapp/webhook');

        // Per-clinic WhatsApp status overview
        $clinicStats = DB::table('clinics')
            ->select('id', 'name', 'whatsapp_phone_number_id', 'is_active')
            ->orderBy('name')
            ->get()
            ->map(function ($clinic) {
                $clinic->wa_configured = !empty($clinic->whatsapp_phone_number_id);
                return $clinic;
            });

        $configuredCount = $clinicStats->where('wa_configured', true)->count();

        return view('admin.whatsapp-settings.index', compact(
            'settings', 'webhookUrl', 'clinicStats', 'configuredCount'
        ));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'wa_phone_number_id' => 'nullable|string|max:50',
            'wa_waba_id'         => 'nullable|string|max:50',
            'wa_access_token'    => 'nullable|string',
            'wa_app_secret'      => 'nullable|string|max:100',
            'wa_verify_token'    => 'nullable|string|max:100',
        ]);

        foreach ($validated as $key => $value) {
            DB::table('platform_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value ?? '', 'updated_at' => now()]
            );
        }

        return back()->with('success', 'WhatsApp platform settings saved successfully.');
    }
}
