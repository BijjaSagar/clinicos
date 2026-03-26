<?php

namespace App\Http\Controllers\Abdm;

use App\Http\Controllers\Controller;
use App\Services\AbdmService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AbdmController extends Controller
{
    protected AbdmService $abdmService;

    public function __construct(AbdmService $abdmService)
    {
        $this->abdmService = $abdmService;
        Log::info('AbdmController initialized');
    }

    /**
     * Verify ABHA ID
     * POST /api/v1/abdm/verify-abha
     */
    public function verifyAbha(Request $request): JsonResponse
    {
        Log::info('AbdmController.verifyAbha: Request received', [
            'abha_id' => $request->input('abha_id'),
        ]);

        $request->validate([
            'abha_id' => 'required|string|min:10',
        ]);

        $result = $this->abdmService->verifyAbhaId($request->input('abha_id'));

        if ($result['success']) {
            Log::info('AbdmController.verifyAbha: Verification successful');
            return response()->json([
                'success' => true,
                'data' => $result['data'],
            ]);
        }

        Log::warning('AbdmController.verifyAbha: Verification failed', [
            'error' => $result['error'],
        ]);

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 400);
    }

    /**
     * Link ABHA to patient
     * POST /api/v1/abdm/link-abha
     */
    public function linkAbha(Request $request): JsonResponse
    {
        Log::info('AbdmController.linkAbha: Request received', [
            'patient_id' => $request->input('patient_id'),
        ]);

        $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'abha_id' => 'required|string|min:10',
            'abha_address' => 'required|string',
        ]);

        $success = $this->abdmService->linkAbhaToPatient(
            $request->input('patient_id'),
            $request->input('abha_id'),
            $request->input('abha_address')
        );

        if ($success) {
            Log::info('AbdmController.linkAbha: ABHA linked successfully');
            return response()->json([
                'success' => true,
                'message' => 'ABHA ID linked successfully',
            ]);
        }

        Log::error('AbdmController.linkAbha: Failed to link ABHA');
        return response()->json([
            'success' => false,
            'error' => 'Failed to link ABHA ID',
        ], 500);
    }

    /**
     * Request consent for health records
     * POST /api/v1/abdm/request-consent
     */
    public function requestConsent(Request $request): JsonResponse
    {
        Log::info('AbdmController.requestConsent: Request received', [
            'clinic_id' => $request->input('clinic_id'),
            'patient_id' => $request->input('patient_id'),
        ]);

        $request->validate([
            'clinic_id' => 'required|integer|exists:clinics,id',
            'patient_id' => 'required|integer|exists:patients,id',
            'purposes' => 'required|array',
            'purposes.*' => 'string|in:OPConsultation,Prescription,DiagnosticReport,ImmunizationRecord,DischargeSummary,HealthDocumentRecord,WellnessRecord',
        ]);

        $requestId = $this->abdmService->requestConsent(
            $request->input('clinic_id'),
            $request->input('patient_id'),
            $request->input('purposes')
        );

        if ($requestId) {
            Log::info('AbdmController.requestConsent: Consent request sent', [
                'request_id' => $requestId,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'request_id' => $requestId,
                    'status' => 'REQUESTED',
                    'message' => 'Consent request sent to patient\'s PHR app',
                ],
            ]);
        }

        Log::error('AbdmController.requestConsent: Failed to send consent request');
        return response()->json([
            'success' => false,
            'error' => 'Failed to send consent request',
        ], 500);
    }

    /**
     * Handle consent callback from ABDM
     * POST /api/v1/abdm/callback/consent
     */
    public function consentCallback(Request $request): JsonResponse
    {
        Log::info('AbdmController.consentCallback: Callback received', [
            'headers' => $request->headers->all(),
        ]);

        $success = $this->abdmService->handleConsentCallback($request->all());

        if ($success) {
            Log::info('AbdmController.consentCallback: Callback processed successfully');
            return response()->json(['success' => true]);
        }

        Log::error('AbdmController.consentCallback: Failed to process callback');
        return response()->json(['success' => false], 500);
    }

    /**
     * Push health record to ABDM
     * POST /api/v1/abdm/push-record
     */
    public function pushRecord(Request $request): JsonResponse
    {
        Log::info('AbdmController.pushRecord: Request received', [
            'visit_id' => $request->input('visit_id'),
        ]);

        $request->validate([
            'visit_id' => 'required|integer|exists:visits,id',
        ]);

        $success = $this->abdmService->pushHealthRecord($request->input('visit_id'));

        if ($success) {
            Log::info('AbdmController.pushRecord: Health record pushed successfully');
            return response()->json([
                'success' => true,
                'message' => 'Health record pushed to ABDM successfully',
            ]);
        }

        Log::error('AbdmController.pushRecord: Failed to push health record');
        return response()->json([
            'success' => false,
            'error' => 'Failed to push health record',
        ], 500);
    }

    /**
     * Handle data request from PHR app
     * POST /api/v1/abdm/callback/data-request
     */
    public function dataRequestCallback(Request $request): JsonResponse
    {
        Log::info('AbdmController.dataRequestCallback: Request received', [
            'headers' => $request->headers->all(),
        ]);

        $result = $this->abdmService->handleDataRequest($request->all());

        if ($result['success']) {
            Log::info('AbdmController.dataRequestCallback: Data request processed');
            return response()->json([
                'success' => true,
                'data' => $result['data'],
            ]);
        }

        Log::error('AbdmController.dataRequestCallback: Failed to process data request');
        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 500);
    }

    /**
     * Get HI types for UI
     * GET /api/v1/abdm/hi-types
     */
    public function getHiTypes(): JsonResponse
    {
        Log::info('AbdmController.getHiTypes: Request received');

        return response()->json([
            'success' => true,
            'data' => AbdmService::getHiTypes(),
        ]);
    }

    /**
     * Create care context for a visit
     * POST /api/v1/abdm/care-context
     */
    public function createCareContext(Request $request): JsonResponse
    {
        Log::info('AbdmController.createCareContext: Request received', [
            'visit_id' => $request->input('visit_id'),
        ]);

        $request->validate([
            'visit_id' => 'required|integer|exists:visits,id',
        ]);

        $careContext = $this->abdmService->createCareContext($request->input('visit_id'));

        if ($careContext) {
            Log::info('AbdmController.createCareContext: Care context created', [
                'reference_number' => $careContext->reference_number,
            ]);

            return response()->json([
                'success' => true,
                'data' => $careContext->toArray(),
            ]);
        }

        Log::error('AbdmController.createCareContext: Failed to create care context');
        return response()->json([
            'success' => false,
            'error' => 'Failed to create care context',
        ], 500);
    }

    /**
     * Get patient's consent status
     * GET /api/v1/abdm/patients/{patientId}/consents
     */
    public function getPatientConsents(int $patientId): JsonResponse
    {
        Log::info('AbdmController.getPatientConsents: Request received', [
            'patient_id' => $patientId,
        ]);

        $consents = \App\Models\AbdmConsent::where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('AbdmController.getPatientConsents: Consents fetched', [
            'count' => $consents->count(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $consents,
        ]);
    }

    /**
     * Get patient's care contexts
     * GET /api/v1/abdm/patients/{patientId}/care-contexts
     */
    public function getPatientCareContexts(int $patientId): JsonResponse
    {
        Log::info('AbdmController.getPatientCareContexts: Request received', [
            'patient_id' => $patientId,
        ]);

        $contexts = \App\Models\AbdmCareContext::where('patient_id', $patientId)
            ->with('visit')
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('AbdmController.getPatientCareContexts: Care contexts fetched', [
            'count' => $contexts->count(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $contexts,
        ]);
    }
}
