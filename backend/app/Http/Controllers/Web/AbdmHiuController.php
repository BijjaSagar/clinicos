<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AbdmHiuLink;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * ABDM M3 — Health Information User (HIU): receive records from other HIP systems.
 * UI + persistence scaffold; gateway integration is environment-specific.
 */
class AbdmHiuController extends Controller
{
    public function index(): View
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('AbdmHiuController@index', ['clinic_id' => $clinicId]);

        $links = collect();
        $hiuSchemaReady = Schema::hasTable('abdm_hiu_links');

        try {
            if ($hiuSchemaReady) {
                $links = AbdmHiuLink::with('patient')
                    ->where('clinic_id', $clinicId)
                    ->orderByDesc('updated_at')
                    ->limit(50)
                    ->get();
            } else {
                Log::warning('AbdmHiuController: abdm_hiu_links missing — run migrations', [
                    'clinic_id' => $clinicId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('AbdmHiuController@index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $patients = Patient::where('clinic_id', $clinicId)->orderBy('name')->limit(500)->get(['id', 'name', 'abha_id']);

        return view('abdm.hiu', compact('links', 'patients', 'hiuSchemaReady'));
    }

    public function store(Request $request): RedirectResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('AbdmHiuController@store', ['clinic_id' => $clinicId]);

        if (!Schema::hasTable('abdm_hiu_links')) {
            Log::warning('AbdmHiuController@store: table missing');

            return back()->with('error', 'Database is not migrated for HIU links. Run php artisan migrate.');
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'hip_id' => 'nullable|string|max:120',
            'care_context_reference' => 'nullable|string|max:200',
            'status' => 'nullable|in:pending,linked,error',
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);
        abort_unless($patient->clinic_id === $clinicId, 403);

        $row = AbdmHiuLink::create([
            'clinic_id' => $clinicId,
            'patient_id' => $patient->id,
            'hip_id' => $validated['hip_id'] ?? null,
            'care_context_reference' => $validated['care_context_reference'] ?? null,
            'status' => $validated['status'] ?? 'pending',
            'gateway_payload' => ['note' => 'Scaffold entry; connect ABDM HIU gateway in production.'],
        ]);

        Log::info('AbdmHiuController: link saved', ['id' => $row->id]);

        return back()->with('success', 'HIU link record saved (scaffold).');
    }
}
