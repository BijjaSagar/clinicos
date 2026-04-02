<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShiftHandoverController extends Controller
{
    public function index()
    {
        $clinicId = auth()->user()->clinic_id;

        $wards = DB::table('wards')
            ->where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->get();

        // Active IPD admissions for patient-level notes
        $admissions = DB::table('ipd_admissions')
            ->join('patients', 'ipd_admissions.patient_id', '=', 'patients.id')
            ->leftJoin('wards', 'ipd_admissions.ward_id', '=', 'wards.id')
            ->leftJoin('beds', 'ipd_admissions.bed_id', '=', 'beds.id')
            ->where('ipd_admissions.clinic_id', $clinicId)
            ->where('ipd_admissions.status', 'admitted')
            ->select(
                'ipd_admissions.id',
                'ipd_admissions.admission_number',
                'patients.name as patient_name',
                'wards.name as ward_name',
                'beds.bed_number'
            )
            ->orderBy('wards.name')
            ->orderBy('beds.bed_number')
            ->get();

        // Recent handover notes
        $recentNotes = DB::table('shift_handover_notes')
            ->join('users', 'shift_handover_notes.handed_by', '=', 'users.id')
            ->leftJoin('wards', 'shift_handover_notes.ward_id', '=', 'wards.id')
            ->where('shift_handover_notes.clinic_id', $clinicId)
            ->orderByDesc('shift_handover_notes.created_at')
            ->limit(10)
            ->select(
                'shift_handover_notes.*',
                'users.name as staff_name',
                'wards.name as ward_name'
            )
            ->get()
            ->map(function ($note) {
                $note->patient_notes = json_decode($note->patient_notes ?? '[]', true);
                return $note;
            });

        return view('ipd.shift-handover', compact('wards', 'admissions', 'recentNotes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ward_id'       => ['nullable', 'integer'],
            'shift'         => ['required', 'in:morning,afternoon,night'],
            'handover_date' => ['required', 'date'],
            'general_notes' => ['required', 'string', 'max:5000'],
            'patient_notes' => ['nullable', 'array'],
            'patient_notes.*.admission_id' => ['required', 'integer'],
            'patient_notes.*.note'         => ['nullable', 'string', 'max:1000'],
        ]);

        $clinicId = auth()->user()->clinic_id;

        // Filter out empty patient notes
        $patientNotes = collect($validated['patient_notes'] ?? [])
            ->filter(fn($n) => !empty(trim($n['note'] ?? '')))
            ->values()
            ->toArray();

        DB::table('shift_handover_notes')->insert([
            'clinic_id'      => $clinicId,
            'ward_id'        => $validated['ward_id'] ?? null,
            'handed_by'      => auth()->id(),
            'shift'          => $validated['shift'],
            'handover_date'  => $validated['handover_date'],
            'general_notes'  => $validated['general_notes'],
            'patient_notes'  => json_encode($patientNotes),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        Log::info('Shift handover note saved', ['by' => auth()->id(), 'shift' => $validated['shift']]);

        return back()->with('success', ucfirst($validated['shift']) . ' shift handover saved successfully.');
    }
}
