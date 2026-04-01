<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * ABDM (Ayushman Bharat Digital Mission) Web Controller
 * 
 * Handles ABHA ID creation, linking, and health record management
 * Reference: https://sandbox.abdm.gov.in/docs/
 */
class AbdmWebController extends Controller
{
    private string $baseUrl;
    private ?string $clientId;
    private ?string $clientSecret;

    public function __construct()
    {
        // ABDM Sandbox URLs (switch to production for live)
        $this->baseUrl = config('services.abdm.base_url', 'https://healthidsbx.abdm.gov.in/api');
        $this->clientId = config('services.abdm.client_id');
        $this->clientSecret = config('services.abdm.client_secret');
    }

    /**
     * ABDM Centre Dashboard
     */
    public function index(): View
    {
        Log::info('AbdmWebController@index');
        
        $clinicId = auth()->user()->clinic_id;
        $clinic = auth()->user()->clinic;

        // Calculate ABDM stats
        $stats = [
            'abha_created' => Patient::where('clinic_id', $clinicId)
                ->whereNotNull('abha_id')
                ->count(),
            'abha_this_month' => Patient::where('clinic_id', $clinicId)
                ->whereNotNull('abha_id')
                ->whereMonth('updated_at', now()->month)
                ->count(),
            'abha_linked' => Patient::where('clinic_id', $clinicId)
                ->where('abha_verified', true)
                ->count(),
            'records_shared' => \DB::table('abdm_care_contexts')
                ->where('clinic_id', $clinicId)
                ->count(),
            'prescriptions' => \DB::table('abdm_care_contexts')
                ->where('clinic_id', $clinicId)
                ->where('hi_type', 'Prescription')
                ->count(),
            'diagnostics' => \DB::table('abdm_care_contexts')
                ->where('clinic_id', $clinicId)
                ->where('hi_type', 'DiagnosticReport')
                ->count(),
            'discharge' => \DB::table('abdm_care_contexts')
                ->where('clinic_id', $clinicId)
                ->where('hi_type', 'DischargeSummary')
                ->count(),
            'consents' => \DB::table('abdm_consents')
                ->where('clinic_id', $clinicId)
                ->where('status', 'GRANTED')
                ->count(),
            'pending_consents' => \DB::table('abdm_consents')
                ->where('clinic_id', $clinicId)
                ->where('status', 'REQUESTED')
                ->count(),
            'expired_consents' => \DB::table('abdm_consents')
                ->where('clinic_id', $clinicId)
                ->where('status', 'EXPIRED')
                ->count(),
        ];

        return view('abdm.index', compact('stats', 'clinic'));
    }

    /**
     * Generate Aadhaar OTP for ABHA creation
     */
    public function generateAadhaarOtp(Request $request): JsonResponse
    {
        Log::info('AbdmWebController@generateAadhaarOtp');

        $validated = $request->validate([
            'aadhaar' => ['required', 'string', 'regex:/^\d{12}$/'],
        ]);

        try {
            // Get access token
            $token = $this->getAccessToken();
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to authenticate with ABDM gateway',
                ], 500);
            }

            // Call ABDM API to generate Aadhaar OTP
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl . '/v1/registration/aadhaar/generateOtp', [
                'aadhaar' => $validated['aadhaar'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'txnId' => $data['txnId'] ?? null,
                    'message' => 'OTP sent to Aadhaar-linked mobile',
                ]);
            } else {
                Log::error('ABDM Aadhaar OTP generation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json([
                    'success' => false,
                    'error' => $response->json()['message'] ?? 'Failed to generate OTP',
                ], $response->status());
            }
        } catch (\Throwable $e) {
            Log::error('ABDM Aadhaar OTP error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Service temporarily unavailable',
            ], 500);
        }
    }

    /**
     * Verify Aadhaar OTP and create ABHA
     */
    public function verifyAadhaarOtp(Request $request): JsonResponse
    {
        Log::info('AbdmWebController@verifyAadhaarOtp');

        $validated = $request->validate([
            'txnId' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
        ]);

        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'Auth failed'], 500);
            }

            // Verify OTP
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/v1/registration/aadhaar/verifyOtp', [
                'txnId' => $validated['txnId'],
                'otp' => $validated['otp'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // If patient_id provided, link ABHA to patient
                if (!empty($validated['patient_id'])) {
                    $patient = Patient::find($validated['patient_id']);
                    if ($patient && $patient->clinic_id === auth()->user()->clinic_id) {
                        $patient->update([
                            'abha_id' => $data['healthIdNumber'] ?? null,
                            'abha_address' => $data['healthId'] ?? null,
                            'abha_verified' => true,
                        ]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'abha_id' => $data['healthIdNumber'] ?? null,
                    'abha_address' => $data['healthId'] ?? null,
                    'name' => $data['name'] ?? null,
                    'message' => 'ABHA ID created successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $response->json()['message'] ?? 'OTP verification failed',
                ], $response->status());
            }
        } catch (\Throwable $e) {
            Log::error('ABDM OTP verify error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Service error'], 500);
        }
    }

    /**
     * Generate Mobile OTP for ABHA creation
     */
    public function generateMobileOtp(Request $request): JsonResponse
    {
        Log::info('AbdmWebController@generateMobileOtp');

        $validated = $request->validate([
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
        ]);

        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'Auth failed'], 500);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/v1/registration/mobile/generateOtp', [
                'mobile' => $validated['mobile'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'txnId' => $data['txnId'] ?? null,
                    'message' => 'OTP sent to mobile number',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $response->json()['message'] ?? 'Failed to generate OTP',
                ], $response->status());
            }
        } catch (\Throwable $e) {
            Log::error('ABDM Mobile OTP error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Service error'], 500);
        }
    }

    /**
     * Verify Mobile OTP and create ABHA
     */
    public function verifyMobileOtp(Request $request): JsonResponse
    {
        Log::info('AbdmWebController@verifyMobileOtp');

        $validated = $request->validate([
            'txnId' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
            'name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:M,F,O'],
            'yearOfBirth' => ['required', 'integer', 'min:1900', 'max:' . date('Y')],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
        ]);

        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'Auth failed'], 500);
            }

            // Verify OTP first
            $verifyResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/v1/registration/mobile/verifyOtp', [
                'txnId' => $validated['txnId'],
                'otp' => $validated['otp'],
            ]);

            if (!$verifyResponse->successful()) {
                return response()->json([
                    'success' => false,
                    'error' => $verifyResponse->json()['message'] ?? 'OTP verification failed',
                ], $verifyResponse->status());
            }

            // Create ABHA with profile details
            $createResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/v1/registration/mobile/createHealthId', [
                'txnId' => $validated['txnId'],
                'name' => $validated['name'],
                'gender' => $validated['gender'],
                'yearOfBirth' => $validated['yearOfBirth'],
            ]);

            if ($createResponse->successful()) {
                $data = $createResponse->json();
                
                // Link to patient if provided
                if (!empty($validated['patient_id'])) {
                    $patient = Patient::find($validated['patient_id']);
                    if ($patient && $patient->clinic_id === auth()->user()->clinic_id) {
                        $patient->update([
                            'abha_id' => $data['healthIdNumber'] ?? null,
                            'abha_address' => $data['healthId'] ?? null,
                            'abha_verified' => true,
                        ]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'abha_id' => $data['healthIdNumber'] ?? null,
                    'abha_address' => $data['healthId'] ?? null,
                    'message' => 'ABHA ID created successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $createResponse->json()['message'] ?? 'ABHA creation failed',
                ], $createResponse->status());
            }
        } catch (\Throwable $e) {
            Log::error('ABDM Mobile ABHA creation error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Service error'], 500);
        }
    }

    /**
     * Link existing ABHA to patient
     */
    public function linkAbha(Request $request): JsonResponse
    {
        Log::info('AbdmWebController@linkAbha');

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'abha_id' => ['required', 'string', 'regex:/^\d{14}$/'],
            'abha_address' => ['nullable', 'string'],
        ]);

        try {
            $patient = Patient::find($validated['patient_id']);
            if (!$patient || $patient->clinic_id !== auth()->user()->clinic_id) {
                return response()->json(['success' => false, 'error' => 'Patient not found'], 404);
            }

            // TODO: Verify ABHA exists via ABDM API before linking
            
            $patient->update([
                'abha_id' => $validated['abha_id'],
                'abha_address' => $validated['abha_address'] ?? null,
                'abha_verified' => false, // Will be verified on first health record push
            ]);

            Log::info('ABHA linked to patient', ['patient_id' => $patient->id, 'abha_id' => $validated['abha_id']]);

            return response()->json([
                'success' => true,
                'message' => 'ABHA ID linked to patient successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ABHA link error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to link ABHA'], 500);
        }
    }

    /**
     * Search ABHA by health ID
     */
    public function searchAbha(Request $request): JsonResponse
    {
        Log::info('AbdmWebController@searchAbha');

        $validated = $request->validate([
            'health_id' => ['required', 'string'],
        ]);

        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return response()->json(['success' => false, 'error' => 'Auth failed'], 500);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/v1/search/searchByHealthId', [
                'healthId' => $validated['health_id'],
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'ABHA not found',
                ], 404);
            }
        } catch (\Throwable $e) {
            Log::error('ABHA search error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Service error'], 500);
        }
    }

    /**
     * Generate Facility QR Code for "Scan and Share"
     */
    public function getFacilityQr(): JsonResponse
    {
        Log::info('AbdmWebController@getFacilityQr');

        $clinic = auth()->user()->clinic;
        
        if (!$clinic->hfr_id) {
            return response()->json([
                'success' => false,
                'error' => 'Clinic is not registered with HFR',
            ], 400);
        }

        // Generate QR data for PHR app scanning
        $qrData = [
            'facilityId' => $clinic->hfr_facility_id,
            'hfrId' => $clinic->hfr_id,
            'facilityName' => $clinic->name,
            'hipCode' => 'CLINICOS_' . $clinic->id,
        ];

        return response()->json([
            'success' => true,
            'qr_data' => base64_encode(json_encode($qrData)),
            'facility_name' => $clinic->name,
            'hfr_id' => $clinic->hfr_id,
        ]);
    }

    /**
     * Get ABDM Gateway Access Token
     */
    private function getAccessToken(): ?string
    {
        if (!$this->clientId || !$this->clientSecret) {
            Log::warning('ABDM credentials not configured');
            return null;
        }

        try {
            $response = Http::post($this->baseUrl . '/v1/auth/cert', [
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                return $response->json()['accessToken'] ?? null;
            }
            
            Log::error('ABDM auth failed', ['status' => $response->status()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('ABDM auth error', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
