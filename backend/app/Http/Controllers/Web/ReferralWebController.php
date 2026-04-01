<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Referral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ReferralWebController extends Controller
{
    public function index(Request $request): View
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('ReferralWebController@index', ['clinic_id' => $clinicId]);

        $patients = Patient::where('clinic_id', $clinicId)->orderBy('name')->get(['id', 'name']);
        $referralsSchemaReady = Schema::hasTable('referrals');

        $referrals = new LengthAwarePaginator([], 0, 25, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        try {
            if ($referralsSchemaReady) {
                $q = Referral::with(['patient', 'fromDoctor'])
                    ->where('clinic_id', $clinicId)
                    ->orderByDesc('created_at');

                if ($request->filled('status')) {
                    $q->where('status', $request->status);
                }

                $referrals = $q->paginate(25);
            } else {
                Log::warning('ReferralWebController: referrals table missing — run migrations', [
                    'clinic_id' => $clinicId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('ReferralWebController@index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return view('referrals.index', compact('referrals', 'patients', 'referralsSchemaReady'));
    }

    public function store(Request $request): RedirectResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('ReferralWebController@store', ['clinic_id' => $clinicId, 'input_keys' => array_keys($request->all())]);

        if (!Schema::hasTable('referrals')) {
            Log::warning('ReferralWebController@store: referrals table missing');

            return back()->with('error', 'Referrals table missing. Run php artisan migrate.');
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'to_specialty' => 'nullable|string|max:120',
            'to_facility_name' => 'nullable|string|max:200',
            'to_doctor_name' => 'nullable|string|max:200',
            'urgency' => 'nullable|in:routine,urgent,emergency',
            'reason' => 'nullable|string|max:5000',
            'clinical_summary' => 'nullable|string|max:8000',
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);
        abort_unless($patient->clinic_id === $clinicId, 403);

        $ref = Referral::create([
            'clinic_id' => $clinicId,
            'patient_id' => $validated['patient_id'],
            'visit_id' => $validated['visit_id'] ?? null,
            'from_doctor_id' => auth()->id(),
            'to_specialty' => $validated['to_specialty'] ?? null,
            'to_facility_name' => $validated['to_facility_name'] ?? null,
            'to_doctor_name' => $validated['to_doctor_name'] ?? null,
            'urgency' => $validated['urgency'] ?? 'routine',
            'reason' => $validated['reason'] ?? null,
            'clinical_summary' => $validated['clinical_summary'] ?? null,
            'status' => 'draft',
        ]);

        Log::info('ReferralWebController: referral created', ['id' => $ref->id]);

        return back()->with('success', 'Referral saved as draft.');
    }

    public function updateStatus(Request $request, Referral $referral): RedirectResponse
    {
        Log::info('ReferralWebController@updateStatus', ['referral_id' => $referral->id]);

        abort_unless($referral->clinic_id === auth()->user()->clinic_id, 403);

        $validated = $request->validate([
            'status' => 'required|in:draft,sent,acknowledged,completed,cancelled',
        ]);

        $referral->update([
            'status' => $validated['status'],
            'sent_at' => $validated['status'] === 'sent' ? ($referral->sent_at ?? now()) : $referral->sent_at,
        ]);

        Log::info('ReferralWebController: status updated', ['referral_id' => $referral->id, 'status' => $validated['status']]);

        return back()->with('success', 'Referral status updated.');
    }
}
