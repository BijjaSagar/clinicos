<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClinicTpaConfig;
use App\Models\Patient;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Insurance/TPA Billing Controller
 * Handles insurance claims, TPA management, and cashless settlement
 */
class InsuranceController extends Controller
{
    /**
     * Common Indian TPAs
     */
    private array $commonTPAs = [
        'ICICILOMBARD' => ['name' => 'ICICI Lombard', 'code' => 'ICICIL'],
        'STARHEALTH' => ['name' => 'Star Health', 'code' => 'STAR'],
        'MAXBUPA' => ['name' => 'Max Bupa (Niva Bupa)', 'code' => 'NIVAB'],
        'BAJAJ' => ['name' => 'Bajaj Allianz', 'code' => 'BAJAJ'],
        'NEWINDA' => ['name' => 'New India Assurance', 'code' => 'NEWIN'],
        'ORIENTAL' => ['name' => 'Oriental Insurance', 'code' => 'ORIEN'],
        'UNITED' => ['name' => 'United India', 'code' => 'UNITE'],
        'HDFC' => ['name' => 'HDFC ERGO', 'code' => 'HDFC'],
        'SBI' => ['name' => 'SBI General', 'code' => 'SBI'],
        'RELIANCE' => ['name' => 'Reliance General', 'code' => 'RELIA'],
        'TATA' => ['name' => 'Tata AIG', 'code' => 'TATA'],
        'CHOLAMANDALAM' => ['name' => 'Cholamandalam', 'code' => 'CHOLA'],
        'MDNINDIA' => ['name' => 'MDIndia', 'code' => 'MDIND'],
        'HEALTHINDIA' => ['name' => 'Health India TPA', 'code' => 'HIND'],
        'PARAMOUNT' => ['name' => 'Paramount TPA', 'code' => 'PARAM'],
        'MEDICARE' => ['name' => 'Medicare TPA', 'code' => 'MEDIC'],
        'VIDAL' => ['name' => 'Vidal Health TPA', 'code' => 'VIDAL'],
        'RAKSHA' => ['name' => 'Raksha TPA', 'code' => 'RAKSH'],
        'MEDSAVE' => ['name' => 'Medsave Healthcare', 'code' => 'MEDSA'],
        'FAMILY' => ['name' => 'Family Health Plan TPA', 'code' => 'FAMIL'],
    ];

    /**
     * Show insurance claims dashboard
     */
    public function index(): View
    {
        Log::info('InsuranceController: Loading insurance dashboard');

        $clinicId = auth()->user()->clinic_id;

        $stats = [
            'pending_claims' => 0,
            'approved_claims' => 0,
            'total_claimed_amount' => 0,
            'total_settled_amount' => 0,
        ];
        $recentClaims = collect();
        $pendingPreAuths = collect();
        $insuranceSchemaReady = Schema::hasTable('insurance_claims') && Schema::hasTable('insurance_preauths');

        try {
            if ($insuranceSchemaReady) {
                $stats = [
                    'pending_claims' => DB::table('insurance_claims')
                        ->where('clinic_id', $clinicId)
                        ->where('status', 'pending')
                        ->count(),
                    'approved_claims' => DB::table('insurance_claims')
                        ->where('clinic_id', $clinicId)
                        ->where('status', 'approved')
                        ->whereMonth('created_at', now()->month)
                        ->count(),
                    'total_claimed_amount' => DB::table('insurance_claims')
                        ->where('clinic_id', $clinicId)
                        ->whereMonth('created_at', now()->month)
                        ->sum('claim_amount') ?? 0,
                    'total_settled_amount' => DB::table('insurance_claims')
                        ->where('clinic_id', $clinicId)
                        ->where('status', 'settled')
                        ->whereMonth('created_at', now()->month)
                        ->sum('settled_amount') ?? 0,
                ];

                $recentClaims = DB::table('insurance_claims')
                    ->where('insurance_claims.clinic_id', $clinicId)
                    ->join('patients', 'insurance_claims.patient_id', '=', 'patients.id')
                    ->select('insurance_claims.*', 'patients.name as patient_name', 'patients.phone as patient_phone')
                    ->orderByDesc('insurance_claims.created_at')
                    ->limit(20)
                    ->get();

                $pendingPreAuths = DB::table('insurance_preauths')
                    ->where('insurance_preauths.clinic_id', $clinicId)
                    ->whereIn('status', ['pending', 'query'])
                    ->join('patients', 'insurance_preauths.patient_id', '=', 'patients.id')
                    ->select('insurance_preauths.*', 'patients.name as patient_name')
                    ->orderByDesc('insurance_preauths.created_at')
                    ->limit(10)
                    ->get();
            } else {
                Log::warning('InsuranceController: insurance tables missing — run migrations', [
                    'clinic_id' => $clinicId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('InsuranceController: index query failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $tpaConfigs = collect();
        if (Schema::hasTable('clinic_tpa_configs')) {
            try {
                $tpaConfigs = ClinicTpaConfig::query()
                    ->where('clinic_id', $clinicId)
                    ->orderBy('tpa_name')
                    ->get();
                Log::info('InsuranceController: TPA configs loaded', ['count' => $tpaConfigs->count()]);
            } catch (\Throwable $e) {
                Log::error('InsuranceController: TPA configs query failed', ['error' => $e->getMessage()]);
            }
        }

        return view('insurance.index', compact(
            'stats',
            'recentClaims',
            'pendingPreAuths',
            'insuranceSchemaReady',
            'tpaConfigs'
        ));
    }

    /**
     * Show pre-authorization form
     */
    public function createPreAuth(Patient $patient): View
    {
        Log::info('InsuranceController: Pre-auth form', ['patient_id' => $patient->id]);
        
        abort_unless(auth()->user()->clinic_id === $patient->clinic_id, 403);

        $savedTpaConfigs = collect();
        if (Schema::hasTable('clinic_tpa_configs')) {
            try {
                $savedTpaConfigs = ClinicTpaConfig::query()
                    ->where('clinic_id', auth()->user()->clinic_id)
                    ->where('is_active', true)
                    ->orderBy('tpa_name')
                    ->get();
            } catch (\Throwable $e) {
                Log::error('InsuranceController: saved TPA list failed', ['error' => $e->getMessage()]);
            }
        }

        return view('insurance.preauth', [
            'patient' => $patient,
            'tpas' => $this->commonTPAs,
            'savedTpaConfigs' => $savedTpaConfigs,
        ]);
    }

    /**
     * Submit pre-authorization request
     */
    public function submitPreAuth(Request $request): JsonResponse
    {
        Log::info('InsuranceController: Submitting pre-auth');

        if (!Schema::hasTable('insurance_preauths')) {
            Log::warning('InsuranceController: submitPreAuth — insurance_preauths missing');

            return response()->json([
                'success' => false,
                'error' => 'Insurance tables not installed. Run php artisan migrate.',
            ], 503);
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'tpa_code' => 'required|string',
            'insurance_company' => 'required|string',
            'policy_number' => 'required|string',
            'member_id' => 'required|string',
            'claim_type' => 'required|in:cashless,reimbursement',
            'admission_type' => 'required|in:planned,emergency',
            'estimated_amount' => 'required|numeric|min:0',
            'diagnosis_codes' => 'nullable|array',
            'procedure_codes' => 'nullable|array',
            'admission_date' => 'required|date',
            'expected_discharge' => 'nullable|date',
            'treatment_details' => 'required|string|max:2000',
            'documents' => 'nullable|array',
        ]);

        $clinicId = auth()->user()->clinic_id;

        try {
            $patient = Patient::findOrFail($validated['patient_id']);
            if ($patient->clinic_id !== $clinicId) {
                Log::warning('InsuranceController: Pre-auth blocked due to cross-clinic patient access', [
                    'clinic_id' => $clinicId,
                    'patient_id' => $patient->id,
                    'patient_clinic_id' => $patient->clinic_id,
                ]);
                return response()->json(['success' => false, 'error' => 'Unauthorized patient access'], 403);
            }

            $preauthId = DB::table('insurance_preauths')->insertGetId([
                'clinic_id' => $clinicId,
                'patient_id' => $validated['patient_id'],
                'tpa_code' => $validated['tpa_code'],
                'insurance_company' => $validated['insurance_company'],
                'policy_number' => $validated['policy_number'],
                'member_id' => $validated['member_id'],
                'claim_type' => $validated['claim_type'],
                'admission_type' => $validated['admission_type'],
                'estimated_amount' => $validated['estimated_amount'],
                'diagnosis_codes' => json_encode($validated['diagnosis_codes'] ?? []),
                'procedure_codes' => json_encode($validated['procedure_codes'] ?? []),
                'admission_date' => $validated['admission_date'],
                'expected_discharge' => $validated['expected_discharge'],
                'treatment_details' => $validated['treatment_details'],
                'status' => 'pending',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('InsuranceController: Pre-auth submitted', ['preauth_id' => $preauthId]);

            return response()->json([
                'success' => true,
                'message' => 'Pre-authorization request submitted successfully',
                'preauth_id' => $preauthId,
            ]);
        } catch (\Throwable $e) {
            Log::error('InsuranceController: Pre-auth error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Submit insurance claim
     */
    public function submitClaim(Request $request): JsonResponse
    {
        Log::info('InsuranceController: Submitting claim');

        if (!Schema::hasTable('insurance_claims')) {
            Log::warning('InsuranceController: submitClaim — insurance_claims missing');

            return response()->json([
                'success' => false,
                'error' => 'Insurance tables not installed. Run php artisan migrate.',
            ], 503);
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'preauth_id' => 'nullable|exists:insurance_preauths,id',
            'tpa_code' => 'required|string',
            'insurance_company' => 'required|string',
            'policy_number' => 'required|string',
            'member_id' => 'required|string',
            'claim_type' => 'required|in:cashless,reimbursement',
            'claim_amount' => 'required|numeric|min:0',
            'diagnosis_codes' => 'nullable|array',
            'procedure_codes' => 'nullable|array',
            'discharge_date' => 'required|date',
            'discharge_summary' => 'required|string|max:5000',
            'documents' => 'nullable|array',
        ]);

        $clinicId = auth()->user()->clinic_id;

        try {
            $patient = Patient::findOrFail($validated['patient_id']);
            if ($patient->clinic_id !== $clinicId) {
                Log::warning('InsuranceController: Claim blocked due to cross-clinic patient access', [
                    'clinic_id' => $clinicId,
                    'patient_id' => $patient->id,
                    'patient_clinic_id' => $patient->clinic_id,
                ]);
                return response()->json(['success' => false, 'error' => 'Unauthorized patient access'], 403);
            }

            if (!empty($validated['invoice_id'])) {
                $invoice = Invoice::find($validated['invoice_id']);
                if (!$invoice || $invoice->clinic_id !== $clinicId || $invoice->patient_id !== $patient->id) {
                    Log::warning('InsuranceController: Claim blocked due to invalid invoice linkage', [
                        'clinic_id' => $clinicId,
                        'patient_id' => $patient->id,
                        'invoice_id' => $validated['invoice_id'],
                        'invoice_clinic_id' => $invoice?->clinic_id,
                        'invoice_patient_id' => $invoice?->patient_id,
                    ]);
                    return response()->json(['success' => false, 'error' => 'Invalid invoice linkage'], 422);
                }
            }

            if (!empty($validated['preauth_id'])) {
                $preauth = DB::table('insurance_preauths')
                    ->where('id', $validated['preauth_id'])
                    ->where('clinic_id', $clinicId)
                    ->where('patient_id', $patient->id)
                    ->first();
                if (!$preauth) {
                    Log::warning('InsuranceController: Claim blocked due to invalid preauth linkage', [
                        'clinic_id' => $clinicId,
                        'patient_id' => $patient->id,
                        'preauth_id' => $validated['preauth_id'],
                    ]);
                    return response()->json(['success' => false, 'error' => 'Invalid pre-authorization linkage'], 422);
                }
            }

            $claimNumber = 'CLM' . $clinicId . date('ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $claimId = DB::table('insurance_claims')->insertGetId([
                'clinic_id' => $clinicId,
                'patient_id' => $validated['patient_id'],
                'invoice_id' => $validated['invoice_id'],
                'preauth_id' => $validated['preauth_id'],
                'claim_number' => $claimNumber,
                'tpa_code' => $validated['tpa_code'],
                'insurance_company' => $validated['insurance_company'],
                'policy_number' => $validated['policy_number'],
                'member_id' => $validated['member_id'],
                'claim_type' => $validated['claim_type'],
                'claim_amount' => $validated['claim_amount'],
                'diagnosis_codes' => json_encode($validated['diagnosis_codes'] ?? []),
                'procedure_codes' => json_encode($validated['procedure_codes'] ?? []),
                'discharge_date' => $validated['discharge_date'],
                'discharge_summary' => $validated['discharge_summary'],
                'status' => 'submitted',
                'status_history' => json_encode([
                    [
                        'status' => 'submitted',
                        'by' => auth()->id(),
                        'at' => now()->toIso8601String(),
                        'note' => 'Claim created',
                    ],
                ]),
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('InsuranceController: Claim submitted', ['claim_id' => $claimId, 'claim_number' => $claimNumber]);

            return response()->json([
                'success' => true,
                'message' => 'Insurance claim submitted successfully',
                'claim_id' => $claimId,
                'claim_number' => $claimNumber,
            ]);
        } catch (\Throwable $e) {
            Log::error('InsuranceController: Claim error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update claim status
     */
    public function updateClaimStatus(Request $request, int $claimId): JsonResponse
    {
        Log::info('InsuranceController: Updating claim status', ['claim_id' => $claimId]);

        $validated = $request->validate([
            'status' => 'required|in:pending,submitted,under_process,query,approved,partially_approved,rejected,settled,closed',
            'settled_amount' => 'nullable|numeric|min:0',
            'tds_amount' => 'nullable|numeric|min:0',
            'approved_amount' => 'nullable|numeric|min:0',
            'rejection_reason' => 'nullable|string|max:500',
            'query_details' => 'nullable|string|max:1000',
            'settlement_date' => 'nullable|date',
            'utr_number' => 'nullable|string',
            'status_note' => 'nullable|string|max:500',
        ]);

        $clinicId = auth()->user()->clinic_id;

        try {
            $claim = DB::table('insurance_claims')
                ->where('id', $claimId)
                ->where('clinic_id', $clinicId)
                ->first();

            if (!$claim) {
                Log::warning('InsuranceController: Claim status update attempted for missing claim', [
                    'claim_id' => $claimId,
                    'clinic_id' => $clinicId,
                ]);
                return response()->json(['success' => false, 'error' => 'Claim not found'], 404);
            }

            if (!$this->isValidClaimStatusTransition((string) $claim->status, $validated['status'])) {
                Log::warning('InsuranceController: Invalid claim status transition blocked', [
                    'claim_id' => $claimId,
                    'from_status' => $claim->status,
                    'to_status' => $validated['status'],
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid status transition from ' . $claim->status . ' to ' . $validated['status'],
                ], 422);
            }

            $updateData = [
                'status' => $validated['status'],
                'updated_at' => now(),
            ];

            if (isset($validated['settled_amount'])) {
                $updateData['settled_amount'] = $validated['settled_amount'];
            }
            if (isset($validated['tds_amount'])) {
                $updateData['tds_amount'] = $validated['tds_amount'];
            }
            if (isset($validated['approved_amount'])) {
                $updateData['approved_amount'] = $validated['approved_amount'];
            }
            if (isset($validated['rejection_reason'])) {
                $updateData['rejection_reason'] = $validated['rejection_reason'];
            }
            if (isset($validated['query_details'])) {
                $updateData['query_details'] = $validated['query_details'];
            }
            if (isset($validated['settlement_date'])) {
                $updateData['settlement_date'] = $validated['settlement_date'];
            }
            if (isset($validated['utr_number'])) {
                $updateData['utr_number'] = $validated['utr_number'];
            }

            $statusHistory = json_decode((string) ($claim->status_history ?? '[]'), true);
            if (!is_array($statusHistory)) {
                $statusHistory = [];
            }
            $statusHistory[] = [
                'from' => $claim->status,
                'to' => $validated['status'],
                'by' => auth()->id(),
                'at' => now()->toIso8601String(),
                'note' => $validated['status_note'] ?? null,
            ];
            $updateData['status_history'] = json_encode($statusHistory);

            DB::table('insurance_claims')
                ->where('id', $claimId)
                ->where('clinic_id', $clinicId)
                ->update($updateData);

            if ($validated['status'] === 'settled' && !empty($claim->invoice_id) && !empty($updateData['settled_amount'])) {
                $invoice = Invoice::where('id', $claim->invoice_id)
                    ->where('clinic_id', $clinicId)
                    ->first();

                if ($invoice) {
                    $invoice->recordPayment(
                        (float) $updateData['settled_amount'],
                        'insurance',
                        $validated['utr_number'] ?? null
                    );
                    Log::info('InsuranceController: Invoice reconciled from insurance settlement', [
                        'claim_id' => $claimId,
                        'invoice_id' => $invoice->id,
                        'settled_amount' => $updateData['settled_amount'],
                        'payment_status' => $invoice->payment_status,
                    ]);
                }
            }

            Log::info('InsuranceController: Claim status updated', ['claim_id' => $claimId, 'status' => $validated['status']]);

            return response()->json([
                'success' => true,
                'message' => 'Claim status updated',
            ]);
        } catch (\Throwable $e) {
            Log::error('InsuranceController: Status update error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get TPA list
     */
    public function getTPAs(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'tpas' => $this->commonTPAs,
        ]);
    }

    /**
     * Save patient insurance details
     */
    public function savePatientInsurance(Request $request, Patient $patient): JsonResponse
    {
        Log::info('InsuranceController: Saving patient insurance', ['patient_id' => $patient->id]);

        abort_unless(auth()->user()->clinic_id === $patient->clinic_id, 403);

        $validated = $request->validate([
            'insurance_company' => 'required|string',
            'policy_number' => 'required|string',
            'member_id' => 'required|string',
            'policy_type' => 'nullable|in:individual,floater,corporate,government',
            'sum_insured' => 'nullable|numeric',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date',
            'tpa_code' => 'nullable|string',
            'employee_id' => 'nullable|string',
            'corporate_name' => 'nullable|string',
        ]);

        try {
            $insuranceData = $patient->insurance_details ?? [];
            $insuranceData[] = [
                'id' => uniqid(),
                'insurance_company' => $validated['insurance_company'],
                'policy_number' => $validated['policy_number'],
                'member_id' => $validated['member_id'],
                'policy_type' => $validated['policy_type'] ?? 'individual',
                'sum_insured' => $validated['sum_insured'] ?? null,
                'valid_from' => $validated['valid_from'] ?? null,
                'valid_to' => $validated['valid_to'] ?? null,
                'tpa_code' => $validated['tpa_code'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
                'corporate_name' => $validated['corporate_name'] ?? null,
                'is_primary' => count($insuranceData) === 0,
                'created_at' => now()->toDateTimeString(),
            ];

            $patient->update(['insurance_details' => $insuranceData]);

            Log::info('InsuranceController: Patient insurance saved', ['patient_id' => $patient->id]);

            return response()->json([
                'success' => true,
                'message' => 'Insurance details saved',
            ]);
        } catch (\Throwable $e) {
            Log::error('InsuranceController: Save error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate claim form PDF
     */
    public function generateClaimForm(int $claimId)
    {
        Log::info('InsuranceController: Generating claim form', ['claim_id' => $claimId]);

        $clinicId = auth()->user()->clinic_id;

        $claim = DB::table('insurance_claims')
            ->where('id', $claimId)
            ->where('clinic_id', $clinicId)
            ->first();

        if (!$claim) {
            abort(404);
        }

        $patient = Patient::find($claim->patient_id);
        $clinic = auth()->user()->clinic;

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('insurance.claim-form', compact('claim', 'patient', 'clinic'));

        return $pdf->download('claim_form_' . $claim->claim_number . '.pdf');
    }

    /**
     * Create or update a clinic TPA configuration (e.g. Paramount PARAM).
     */
    public function storeTpaConfig(Request $request): JsonResponse
    {
        Log::info('InsuranceController: storeTpaConfig');

        if (!Schema::hasTable('clinic_tpa_configs')) {
            Log::warning('InsuranceController: storeTpaConfig — clinic_tpa_configs missing');

            return response()->json([
                'success' => false,
                'error' => 'TPA configuration table not installed. Run php artisan migrate.',
            ], 503);
        }

        $validated = $request->validate([
            'tpa_code' => 'required|string|max:20',
            'tpa_name' => 'required|string|max:200',
            'empanelment_id' => 'nullable|string|max:50',
            'provider_id' => 'nullable|string|max:50',
            'rohini_id' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:150',
            'contact_phone' => 'nullable|string|max:15',
            'portal_url' => 'nullable|string|max:500',
            'portal_username' => 'nullable|string|max:100',
            'portal_password' => 'nullable|string|max:200',
            'is_active' => 'nullable|boolean',
        ]);

        $clinicId = auth()->user()->clinic_id;
        $code = strtoupper(trim($validated['tpa_code']));

        try {
            $row = ClinicTpaConfig::query()->firstOrNew([
                'clinic_id' => $clinicId,
                'tpa_code' => $code,
            ]);

            $row->tpa_name = $validated['tpa_name'];
            $row->empanelment_id = $validated['empanelment_id'] ?? null;
            $row->provider_id = $validated['provider_id'] ?? null;
            $row->rohini_id = $validated['rohini_id'] ?? null;
            $row->contact_email = $validated['contact_email'] ?? null;
            $row->contact_phone = $validated['contact_phone'] ?? null;
            $row->portal_url = $validated['portal_url'] ?? null;
            $row->portal_username = $validated['portal_username'] ?? null;
            $row->is_active = array_key_exists('is_active', $validated)
                ? (bool) $validated['is_active']
                : ($row->exists ? $row->is_active : true);

            if (!empty($validated['portal_password'])) {
                $row->portal_password_encrypted = encrypt($validated['portal_password']);
            }

            $row->save();

            Log::info('InsuranceController: TPA config saved', ['id' => $row->id, 'tpa_code' => $code]);

            return response()->json([
                'success' => true,
                'message' => 'TPA configuration saved.',
                'config' => [
                    'id' => $row->id,
                    'tpa_code' => $row->tpa_code,
                    'tpa_name' => $row->tpa_name,
                    'portal_url' => $row->portal_url,
                    'has_portal_password' => !empty($row->portal_password_encrypted),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('InsuranceController: storeTpaConfig failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroyTpaConfig(ClinicTpaConfig $config): JsonResponse
    {
        Log::info('InsuranceController: destroyTpaConfig', ['config_id' => $config->id]);

        abort_unless($config->clinic_id === auth()->user()->clinic_id, 403);

        if (!Schema::hasTable('clinic_tpa_configs')) {
            return response()->json(['success' => false, 'error' => 'Table missing'], 503);
        }

        try {
            $id = $config->id;
            $config->delete();
            Log::info('InsuranceController: TPA config deleted', ['config_id' => $id]);

            return response()->json(['success' => true, 'message' => 'TPA configuration removed.']);
        } catch (\Throwable $e) {
            Log::error('InsuranceController: destroyTpaConfig failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function isValidClaimStatusTransition(string $currentStatus, string $nextStatus): bool
    {
        if ($currentStatus === $nextStatus) {
            return true;
        }

        $allowedTransitions = [
            'pending' => ['submitted', 'query'],
            'submitted' => ['under_process', 'query', 'approved', 'partially_approved', 'rejected'],
            'under_process' => ['query', 'approved', 'partially_approved', 'rejected'],
            'query' => ['under_process', 'approved', 'partially_approved', 'rejected'],
            'approved' => ['settled', 'closed'],
            'partially_approved' => ['settled', 'closed', 'query'],
            'rejected' => ['closed'],
            'settled' => ['closed'],
            'closed' => [],
        ];

        return in_array($nextStatus, $allowedTransitions[$currentStatus] ?? [], true);
    }
}
