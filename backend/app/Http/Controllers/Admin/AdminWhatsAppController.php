<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminWhatsAppController extends Controller
{
    private function getSetting(string $key, $default = null): mixed
    {
        $row = DB::table('system_settings')->where('key', $key)->first();
        return $row?->value ?? $default;
    }

    private function setSetting(string $key, string $value, string $group = 'whatsapp'): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function index()
    {
        $config = [
            'phone_number_id' => $this->getSetting('whatsapp_phone_number_id'),
            'waba_id'         => $this->getSetting('whatsapp_waba_id'),
            'access_token'    => $this->getSetting('whatsapp_access_token'),
            'app_secret'      => $this->getSetting('whatsapp_app_secret'),
            'verify_token'    => $this->getSetting('whatsapp_verify_token', 'clinicos_verify'),
            'is_configured'   => !empty($this->getSetting('whatsapp_access_token')),
        ];
        $webhookUrl = url('/api/v1/whatsapp/webhook');
        $totalClinics = DB::table('clinics')->where('is_active', true)->count();
        return view('admin.whatsapp', compact('config', 'webhookUrl', 'totalClinics'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'phone_number_id' => 'required|string|max:50',
            'waba_id'         => 'nullable|string|max:50',
            'access_token'    => 'required|string',
            'app_secret'      => 'nullable|string|max:200',
            'verify_token'    => 'nullable|string|max:100',
        ]);

        $this->setSetting('whatsapp_phone_number_id', $validated['phone_number_id']);
        $this->setSetting('whatsapp_waba_id', $validated['waba_id'] ?? '');
        $this->setSetting('whatsapp_access_token', $validated['access_token']);
        $this->setSetting('whatsapp_app_secret', $validated['app_secret'] ?? '');
        $this->setSetting('whatsapp_verify_token', $validated['verify_token'] ?? 'clinicos_verify');

        Log::info('Global WhatsApp credentials saved by super admin', ['user' => auth()->id()]);
        return back()->with('success', 'WhatsApp credentials saved. All clinics will now use these credentials.');
    }

    public function test()
    {
        $phoneNumberId = $this->getSetting('whatsapp_phone_number_id');
        $accessToken   = $this->getSetting('whatsapp_access_token');

        if (empty($phoneNumberId) || empty($accessToken)) {
            return response()->json(['success' => false, 'message' => 'Please save credentials first.']);
        }

        try {
            $response = Http::withToken($accessToken)
                ->get("https://graph.facebook.com/v19.0/{$phoneNumberId}");

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'message' => 'Connected! Phone: ' . ($data['display_phone_number'] ?? 'N/A') . ', Quality: ' . ($data['quality_rating'] ?? 'N/A'),
                ]);
            }
            return response()->json(['success' => false, 'message' => 'API error: ' . ($response->json('error.message') ?? 'Unknown')]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
