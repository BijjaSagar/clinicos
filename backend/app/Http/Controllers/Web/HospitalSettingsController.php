<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HospitalSettingsController extends Controller
{
    private function getSettings(int $clinicId): array
    {
        $rows = DB::table('hospital_settings')
            ->where('clinic_id', $clinicId)
            ->pluck('value', 'key')
            ->toArray();

        return array_merge([
            'hospital_name'       => '',
            'hospital_type'       => 'clinic',
            'total_beds'          => 0,
            'icu_beds'            => 0,
            'emergency_beds'      => 0,
            'registration_prefix' => 'IPD',
            'discharge_summary_footer' => '',
            'enable_ipd'          => '0',
            'enable_pharmacy'     => '0',
            'enable_lab'          => '0',
            'enable_opd_queue'    => '0',
        ], $rows);
    }

    public function index()
    {
        $clinicId = auth()->user()->clinic_id;
        $settings = $this->getSettings($clinicId);
        $wards    = DB::table('hospital_wards')->where('clinic_id', $clinicId)->get();
        return view('hospital-settings.index', compact('settings', 'wards'));
    }

    public function update(Request $request)
    {
        $clinicId  = auth()->user()->clinic_id;
        $validated = $request->validate([
            'hospital_name'            => 'required|string|max:255',
            'hospital_type'            => 'required|in:clinic,hospital,nursing_home,polyclinic',
            'total_beds'               => 'nullable|integer|min:0',
            'icu_beds'                 => 'nullable|integer|min:0',
            'emergency_beds'           => 'nullable|integer|min:0',
            'registration_prefix'      => 'nullable|string|max:20',
            'discharge_summary_footer' => 'nullable|string',
            'enable_ipd'               => 'nullable',
            'enable_pharmacy'          => 'nullable',
            'enable_lab'               => 'nullable',
            'enable_opd_queue'         => 'nullable',
        ]);

        foreach ($validated as $key => $value) {
            if (in_array($key, ['enable_ipd','enable_pharmacy','enable_lab','enable_opd_queue'])) {
                $value = $request->has($key) ? '1' : '0';
            }
            DB::table('hospital_settings')->updateOrInsert(
                ['clinic_id' => $clinicId, 'key' => $key],
                ['value' => $value ?? '', 'updated_at' => now()]
            );
        }

        return back()->with('success', 'Hospital settings saved');
    }

    public function storeWard(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:120',
            'floor'  => 'nullable|string|max:30',
            'wing'   => 'nullable|string|max:60',
            'is_icu' => 'nullable',
        ]);
        DB::table('hospital_wards')->insert([
            'clinic_id'  => auth()->user()->clinic_id,
            'name'       => $request->name,
            'floor'      => $request->floor,
            'wing'       => $request->wing,
            'is_icu'     => $request->boolean('is_icu'),
            'is_active'  => 1,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Ward added');
    }
}
