<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SetupWizardController extends Controller
{
    public function index()
    {
        $clinic = auth()->user()->clinic;
        if (!$clinic || $clinic->settings['setup_completed'] ?? false) {
            return redirect()->route('dashboard');
        }
        return view('setup-wizard.index', ['clinic' => $clinic]);
    }

    public function saveStep(Request $request)
    {
        $step = $request->input('step');
        $clinic = auth()->user()->clinic;

        switch ($step) {
            case 'clinic-info':
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'phone' => 'nullable|string|max:20',
                    'email' => 'nullable|email|max:255',
                    'address_line1' => 'nullable|string|max:255',
                    'city' => 'nullable|string|max:100',
                    'state' => 'nullable|string|max:100',
                    'pincode' => 'nullable|string|max:10',
                    'gstin' => 'nullable|string|max:20',
                ]);
                $clinic->update($validated);
                break;

            case 'specialties':
                $validated = $request->validate([
                    'specialties' => 'required|array|min:1',
                    'specialties.*' => 'string',
                ]);
                $clinic->update(['specialties' => $validated['specialties']]);
                break;

            case 'facility':
                $validated = $request->validate([
                    'facility_type' => 'required|in:clinic,hospital,multispecialty_hospital',
                    'licensed_beds' => 'nullable|integer|min:0',
                ]);
                $clinic->update($validated);

                // Auto-enable relevant HIMS features for hospitals
                if ($validated['facility_type'] !== 'clinic') {
                    $features = $clinic->hims_features ?? [];
                    $hospitalDefaults = ['bed_management', 'ipd', 'opd_hospital', 'pharmacy_inventory', 'pharmacy_op_dispensing', 'lis_collection', 'lis_results', 'lis_reports_pdf', 'nursing_notes', 'vitals_chart', 'billing_unified'];
                    foreach ($hospitalDefaults as $f) {
                        $features[$f] = true;
                    }
                    $clinic->update(['hims_features' => $features]);
                }
                break;

            case 'complete':
                $settings = $clinic->settings ?? [];
                $settings['setup_completed'] = true;
                $settings['setup_completed_at'] = now()->toDateTimeString();
                $clinic->update(['settings' => $settings]);
                return response()->json(['redirect' => route('dashboard')]);
        }

        return response()->json(['success' => true]);
    }
}
