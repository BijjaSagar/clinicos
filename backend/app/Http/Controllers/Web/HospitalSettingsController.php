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
        $wards    = DB::table('wards')->where('clinic_id', $clinicId)->get();
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
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'type'       => 'required|in:general,icu,nicu,picu,maternity,surgical,medical,orthopedic,pediatric,emergency,private,semi_private',
            'total_beds' => 'required|integer|min:1',
            'floor'      => 'nullable|string|max:50',
            'notes'      => 'nullable|string',
            'daily_rate' => 'nullable|numeric|min:0',
        ]);

        $clinicId = auth()->user()->clinic_id;

        // Map ward type to bed type (beds table has a narrower enum)
        $bedTypeMap = [
            'icu' => 'icu', 'nicu' => 'nicu', 'picu' => 'icu',
            'maternity' => 'maternity', 'pediatric' => 'pediatric',
        ];
        $bedType = $bedTypeMap[$validated['type']] ?? 'general';

        // DB column is ward_type, not type
        $wardId = DB::table('wards')->insertGetId([
            'name'       => $validated['name'],
            'ward_type'  => $validated['type'],
            'total_beds' => $validated['total_beds'],
            'floor'      => $validated['floor'] ?? null,
            'notes'      => $validated['notes'] ?? null,
            'daily_rate' => $validated['daily_rate'] ?? 0,
            'clinic_id'  => $clinicId,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Auto-create individual bed records
        $beds = [];
        for ($i = 1; $i <= $validated['total_beds']; $i++) {
            $beds[] = [
                'clinic_id'  => $clinicId,
                'ward_id'    => $wardId,
                'bed_number' => $validated['name'] . '-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'bed_type'   => $bedType,
                'status'     => 'available',
                'floor'      => $validated['floor'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('beds')->insert($beds);

        return back()->with('success', "Ward \"{$validated['name']}\" added with {$validated['total_beds']} beds.");
    }
}
