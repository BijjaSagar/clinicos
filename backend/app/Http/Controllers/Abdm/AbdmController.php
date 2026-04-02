<?php

namespace App\Http\Controllers\Abdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ABDM (Ayushman Bharat Digital Mission) integration controller.
 * Stub implementation — full ABDM integration pending.
 */
class AbdmController extends Controller
{
    /**
     * Handle ABDM consent webhook (NHA → ClinicOS).
     */
    public function consentWebhook(Request $request): JsonResponse
    {
        Log::info('ABDM consent webhook received', ['payload' => $request->all()]);
        return response()->json(['status' => 'acknowledged']);
    }

    /**
     * Handle ABDM health-info webhook (NHA → ClinicOS).
     */
    public function healthInfoWebhook(Request $request): JsonResponse
    {
        Log::info('ABDM health-info webhook received', ['payload' => $request->all()]);
        return response()->json(['status' => 'acknowledged']);
    }

    /**
     * Handle ABDM notify webhook (NHA → ClinicOS).
     */
    public function notifyWebhook(Request $request): JsonResponse
    {
        Log::info('ABDM notify webhook received', ['payload' => $request->all()]);
        return response()->json(['status' => 'acknowledged']);
    }

    /**
     * Link or create an ABHA (Ayushman Bharat Health Account) for a patient.
     */
    public function linkAbha(Request $request, int $patientId = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ABDM integration not yet configured. Please contact support.',
        ], 501);
    }

    /**
     * Verify an ABHA ID/address.
     */
    public function verifyAbha(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ABDM integration not yet configured. Please contact support.',
        ], 501);
    }

    /**
     * Request health data consent from a patient.
     */
    public function requestConsent(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ABDM integration not yet configured. Please contact support.',
        ], 501);
    }

    /**
     * Push a clinical record to ABDM.
     */
    public function pushRecord(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ABDM integration not yet configured. Please contact support.',
        ], 501);
    }

    /**
     * Create a care context for a patient.
     */
    public function createCareContext(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ABDM integration not yet configured. Please contact support.',
        ], 501);
    }

    /**
     * Get supported HI (Health Information) types.
     */
    public function getHiTypes(Request $request): JsonResponse
    {
        return response()->json([
            'hi_types' => [
                'Prescription',
                'DiagnosticReport',
                'OPConsultation',
                'DischargeSummary',
                'ImmunizationRecord',
                'HealthDocumentRecord',
                'WellnessRecord',
            ],
        ]);
    }

    /**
     * Get all consents for a specific patient.
     */
    public function getPatientConsents(Request $request, int $patientId): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ABDM integration not yet configured. Please contact support.',
            'consents' => [],
        ], 501);
    }

    /**
     * Get all care contexts for a specific patient.
     */
    public function getPatientCareContexts(Request $request, int $patientId): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ABDM integration not yet configured. Please contact support.',
            'care_contexts' => [],
        ], 501);
    }
}
