<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SettingsWebController extends Controller
{
    public function index()
    {
        Log::info('SettingsWebController@index', ['user' => auth()->id()]);

        try {
            $clinic = auth()->user()->clinic;
            $user = auth()->user();

            Log::info('SettingsWebController loaded', ['clinic_id' => $clinic?->id]);

            return view('settings.index', compact('clinic', 'user'));
        } catch (\Throwable $e) {
            Log::error('SettingsWebController@index error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateClinic(Request $request)
    {
        Log::info('SettingsWebController@updateClinic', $request->all());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'specialty' => 'nullable|string|max:100',
            'address_line1' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gstin' => 'nullable|string|max:20',
            'pan' => 'nullable|string|max:20',
        ]);

        try {
            $clinic = auth()->user()->clinic;
            
            // Handle specialties as array
            $updateData = $validated;
            if (isset($validated['specialty'])) {
                $updateData['specialties'] = [$validated['specialty']];
                unset($updateData['specialty']);
            }
            
            $clinic->update($updateData);

            Log::info('Clinic settings updated', ['clinic_id' => $clinic->id]);

            return back()->with('success', 'Clinic settings updated successfully');
        } catch (\Throwable $e) {
            Log::error('updateClinic error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    public function updateBilling(Request $request)
    {
        Log::info('SettingsWebController@updateBilling', $request->all());

        $validated = $request->validate([
            'invoice_prefix' => 'required|string|max:10',
            'default_gst_rate' => 'required|numeric|min:0|max:100',
            'payment_terms' => 'nullable|string|max:500',
        ]);

        try {
            $clinic = auth()->user()->clinic;
            $clinic->update([
                'settings' => array_merge($clinic->settings ?? [], [
                    'invoice_prefix' => $validated['invoice_prefix'],
                    'default_gst_rate' => $validated['default_gst_rate'],
                    'payment_terms' => $validated['payment_terms'],
                ]),
            ]);

            Log::info('Billing settings updated', ['clinic_id' => $clinic->id]);

            return back()->with('success', 'Billing settings updated successfully');
        } catch (\Throwable $e) {
            Log::error('updateBilling error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to update billing settings: ' . $e->getMessage());
        }
    }
}
