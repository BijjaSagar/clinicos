<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Clinic;
use App\Models\AbdmConsent;
use App\Models\AbdmCareContext;
use App\Models\Visit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AbdmService
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->baseUrl = config('services.abdm.base_url', 'https://dev.abdm.gov.in');
        $this->clientId = config('services.abdm.client_id', '');
        $this->clientSecret = config('services.abdm.client_secret', '');

        Log::info('AbdmService initialized', ['base_url' => $this->baseUrl]);
    }

    // ─── Auth ─────────────────────────────────────────────────────────────────

    /**
     * Get access token from ABDM Gateway (cached)
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'abdm_access_token';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::post("{$this->baseUrl}/gateway/v0.5/sessions", [
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $token = $response->json('accessToken');
                $expiresIn = $response->json('expiresIn', 1800);
                Cache::put($cacheKey, $token, $expiresIn - 60);
                $this->accessToken = $token;

                Log::info('AbdmService.getAccessToken: Token obtained');
                return $token;
            }

            Log::error('AbdmService.getAccessToken: Failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \RuntimeException('Failed to obtain ABDM access token');
        } catch (\Exception $e) {
            Log::error('AbdmService.getAccessToken: Exception', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ─── M1: ABHA Creation (Aadhaar Flow) ─────────────────────────────────────

    /**
     * Generate Aadhaar OTP for ABHA creation
     * POST /v1/registration/aadhaar/generateOtp
     */
    public function generateAadhaarOtp(string $aadhaar): array
    {
        Log::info('AbdmService.generateAadhaarOtp: Initiating', [
            'aadhaar_last4' => substr($aadhaar, -4),
        ]);

        try {
            $encryptedAadhaar = $this->encryptWithPublicKey($aadhaar);

            $response = $this->callAbdmApi('POST', '/v1/registration/aadhaar/generateOtp', [
                'aadhaar' => $encryptedAadhaar,
            ]);

            Log::info('AbdmService.generateAadhaarOtp: OTP generated', [
                'txnId' => $response['txnId'] ?? null,
            ]);

            return [
                'success' => true,
                'txnId' => $response['txnId'] ?? null,
                'message' => $response['message'] ?? 'OTP sent successfully',
            ];
        } catch (\Exception $e) {
            Log::error('AbdmService.generateAadhaarOtp: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify Aadhaar OTP
     * POST /v1/registration/aadhaar/verifyOtp
     */
    public function verifyAadhaarOtp(string $txnId, string $otp): array
    {
        Log::info('AbdmService.verifyAadhaarOtp: Verifying', ['txnId' => $txnId]);

        try {
            $encryptedOtp = $this->encryptWithPublicKey($otp);

            $response = $this->callAbdmApi('POST', '/v1/registration/aadhaar/verifyOtp', [
                'txnId' => $txnId,
                'otp' => $encryptedOtp,
            ]);

            Log::info('AbdmService.verifyAadhaarOtp: Verified', ['txnId' => $txnId]);

            return [
                'success' => true,
                'txnId' => $response['txnId'] ?? $txnId,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('AbdmService.verifyAadhaarOtp: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─── M1: ABHA Creation (Mobile Flow) ──────────────────────────────────────

    /**
     * Generate Mobile OTP for ABHA creation
     */
    public function generateMobileOtp(string $mobile): array
    {
        Log::info('AbdmService.generateMobileOtp: Initiating', [
            'mobile_last4' => substr($mobile, -4),
        ]);

        try {
            $response = $this->callAbdmApi('POST', '/v1/registration/mobile/generateOtp', [
                'mobile' => $mobile,
            ]);

            Log::info('AbdmService.generateMobileOtp: OTP generated', [
                'txnId' => $response['txnId'] ?? null,
            ]);

            return [
                'success' => true,
                'txnId' => $response['txnId'] ?? null,
                'message' => $response['message'] ?? 'OTP sent successfully',
            ];
        } catch (\Exception $e) {
            Log::error('AbdmService.generateMobileOtp: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify Mobile OTP
     */
    public function verifyMobileOtp(string $txnId, string $otp): array
    {
        Log::info('AbdmService.verifyMobileOtp: Verifying', ['txnId' => $txnId]);

        try {
            $response = $this->callAbdmApi('POST', '/v1/registration/mobile/verifyOtp', [
                'txnId' => $txnId,
                'otp' => $otp,
            ]);

            return [
                'success' => true,
                'txnId' => $response['txnId'] ?? $txnId,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('AbdmService.verifyMobileOtp: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─── M1: ABHA ID Management ───────────────────────────────────────────────

    /**
     * Create ABHA (Health ID) after OTP verification
     */
    public function createHealthId(string $txnId, array $profileData): array
    {
        Log::info('AbdmService.createHealthId: Creating', ['txnId' => $txnId]);

        try {
            $payload = array_merge([
                'txnId' => $txnId,
            ], $profileData);

            $response = $this->callAbdmApi('POST', '/v1/registration/aadhaar/createHealthIdWithPreVerified', $payload);

            Log::info('AbdmService.createHealthId: ABHA created', [
                'healthIdNumber' => $response['healthIdNumber'] ?? null,
                'healthId' => $response['healthId'] ?? null,
            ]);

            return [
                'success' => true,
                'healthIdNumber' => $response['healthIdNumber'] ?? null,
                'healthId' => $response['healthId'] ?? null,
                'name' => $response['name'] ?? null,
                'token' => $response['token'] ?? null,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('AbdmService.createHealthId: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Search existing ABHA by Health ID
     */
    public function searchByHealthId(string $healthId): array
    {
        Log::info('AbdmService.searchByHealthId', ['healthId' => $healthId]);

        try {
            $response = $this->callAbdmApi('POST', '/v1/search/searchByHealthId', [
                'healthId' => $healthId,
            ]);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('AbdmService.searchByHealthId: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get ABHA card (PDF/PNG)
     */
    public function getAbhaCard(string $token): string
    {
        Log::info('AbdmService.getAbhaCard: Fetching card');

        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'X-Token' => "Bearer {$token}",
                'Accept' => 'image/png',
            ])->get("{$this->baseUrl}/v1/account/getCard");

            if ($response->successful()) {
                return base64_encode($response->body());
            }

            throw new \RuntimeException('Failed to fetch ABHA card');
        } catch (\Exception $e) {
            Log::error('AbdmService.getAbhaCard: Failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Verify existing ABHA ID
     */
    public function verifyAbha(string $healthId): array
    {
        Log::info('AbdmService.verifyAbha', ['healthId' => $healthId]);

        try {
            $response = $this->callAbdmApi('POST', '/v1/phr/profile/link/profileDetails', [
                'healthId' => $healthId,
            ], ['X-CM-ID' => 'sbx']);

            return [
                'success' => true,
                'data' => [
                    'abha_id' => $response['healthId'] ?? $healthId,
                    'abha_address' => $response['healthIdNumber'] ?? null,
                    'name' => $response['name'] ?? null,
                    'gender' => $response['gender'] ?? null,
                    'dob' => $response['dateOfBirth'] ?? null,
                    'mobile' => $response['mobile'] ?? null,
                    'address' => $response['address'] ?? null,
                    'state' => $response['state'] ?? null,
                    'district' => $response['district'] ?? null,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('AbdmService.verifyAbha: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Link ABHA ID to a patient record
     */
    public function linkAbhaToPatient(int $patientId, string $abhaId, string $abhaAddress): bool
    {
        Log::info('AbdmService.linkAbhaToPatient', [
            'patient_id' => $patientId,
            'abha_id' => $abhaId,
        ]);

        try {
            $patient = Patient::findOrFail($patientId);
            $patient->update([
                'abha_id' => $abhaId,
                'abha_address' => $abhaAddress,
                'abha_verified' => true,
                'abdm_consent_active' => true,
            ]);

            Log::info('AbdmService.linkAbhaToPatient: Linked successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('AbdmService.linkAbhaToPatient: Failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    // ─── M1: Facility QR / Scan & Share ────────────────────────────────────────

    /**
     * Generate facility QR code data URL for Scan & Share
     */
    public function generateFacilityQr(string $hfrId): string
    {
        Log::info('AbdmService.generateFacilityQr', ['hfrId' => $hfrId]);

        $qrPayload = json_encode([
            'hfrId' => $hfrId,
            'type' => 'HIP',
            'scanType' => 'SCAN_AND_SHARE',
            'version' => '1.0',
            'timestamp' => now()->toIso8601String(),
        ]);

        // Generate QR code as base64 data URL using simple SVG approach
        // In production, use a QR library like SimpleSoftwareIO/QrCode
        return $qrPayload;
    }

    /**
     * Process scanned QR data from patient's PHR app
     */
    public function processScanAndShare(string $qrData): array
    {
        Log::info('AbdmService.processScanAndShare: Processing QR');

        try {
            $decoded = json_decode($qrData, true);
            if (!$decoded) {
                return ['success' => false, 'error' => 'Invalid QR data'];
            }

            $healthId = $decoded['healthId'] ?? $decoded['abhaAddress'] ?? null;
            if (!$healthId) {
                return ['success' => false, 'error' => 'No ABHA ID found in QR data'];
            }

            // Verify the ABHA and get profile
            $verification = $this->verifyAbha($healthId);
            if (!$verification['success']) {
                return $verification;
            }

            return [
                'success' => true,
                'data' => $verification['data'],
                'qr_data' => $decoded,
            ];
        } catch (\Exception $e) {
            Log::error('AbdmService.processScanAndShare: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─── M2: Health Information Provider (HIP) ────────────────────────────────

    /**
     * Register a care context for a patient visit
     */
    public function registerCareContext(Patient $patient, Visit $visit): array
    {
        Log::info('AbdmService.registerCareContext', [
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
        ]);

        if (!$patient->abha_id) {
            return ['success' => false, 'error' => 'Patient has no ABHA ID'];
        }

        try {
            $referenceNumber = 'CC-' . $visit->clinic_id . '-' . $visit->id . '-' . time();
            $display = "Visit on " . $visit->created_at->format('d M Y') . " - " . ($visit->diagnosis_text ?? 'Consultation');

            $requestId = Str::uuid()->toString();
            $timestamp = now()->toIso8601String();

            $payload = [
                'requestId' => $requestId,
                'timestamp' => $timestamp,
                'link' => [
                    'accessToken' => $this->getAccessToken(),
                    'patient' => [
                        'referenceNumber' => $patient->abha_id,
                        'display' => $patient->name,
                        'careContexts' => [
                            [
                                'referenceNumber' => $referenceNumber,
                                'display' => $display,
                            ],
                        ],
                    ],
                ],
            ];

            $response = $this->callAbdmApi('POST', '/v0.5/links/link/add-contexts', $payload, [
                'X-CM-ID' => 'sbx',
            ]);

            $careContext = AbdmCareContext::create([
                'clinic_id' => $visit->clinic_id,
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'reference_number' => $referenceNumber,
                'display' => $display,
                'hi_type' => 'OPConsultation',
                'linked_at' => now(),
            ]);

            Log::info('AbdmService.registerCareContext: Created', [
                'reference_number' => $referenceNumber,
            ]);

            return [
                'success' => true,
                'care_context' => $careContext,
            ];
        } catch (\Exception $e) {
            Log::error('AbdmService.registerCareContext: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build a FHIR R4 Bundle from visit data
     */
    public function buildFhirBundle(Visit $visit): array
    {
        $fhirBuilder = new FhirBuilder();
        return $fhirBuilder->buildBundle($visit);
    }

    /**
     * Push a health record to ABDM
     */
    public function pushHealthRecord(string $careContextId, array $fhirBundle): array
    {
        Log::info('AbdmService.pushHealthRecord', ['careContextId' => $careContextId]);

        try {
            $careContext = AbdmCareContext::where('reference_number', $careContextId)->firstOrFail();
            $visit = Visit::with(['patient', 'clinic'])->findOrFail($careContext->visit_id);

            $requestId = Str::uuid()->toString();
            $timestamp = now()->toIso8601String();

            $payload = [
                'requestId' => $requestId,
                'timestamp' => $timestamp,
                'notification' => [
                    'patient' => [
                        'id' => $visit->patient->abha_address,
                    ],
                    'careContexts' => [
                        [
                            'patientReference' => $visit->patient->abha_id,
                            'careContextReference' => $careContext->reference_number,
                        ],
                    ],
                    'hiTypes' => ['OPConsultation'],
                    'date' => $visit->created_at->toIso8601String(),
                    'hip' => [
                        'id' => $visit->clinic->hfr_id,
                    ],
                ],
            ];

            $response = $this->callAbdmApi('POST', '/v0.5/links/link/add-contexts', $payload, [
                'X-CM-ID' => 'sbx',
            ]);

            $careContext->update(['linked_at' => now()]);

            // Store FHIR bundle on the visit
            $visit->update([
                'fhir_bundle' => json_encode($fhirBundle),
                'abdm_pushed_at' => now(),
            ]);

            Log::info('AbdmService.pushHealthRecord: Pushed successfully');

            return ['success' => true, 'data' => $response];
        } catch (\Exception $e) {
            Log::error('AbdmService.pushHealthRecord: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─── M2: Consent Management ───────────────────────────────────────────────

    /**
     * Handle incoming consent request webhook from ABDM
     */
    public function onConsentRequest(array $consentData): void
    {
        Log::info('AbdmService.onConsentRequest: Received', [
            'keys' => array_keys($consentData),
        ]);

        try {
            $requestId = $consentData['requestId'] ?? null;
            $consentStatus = $consentData['notification']['status'] ?? null;
            $consentArtefactId = $consentData['notification']['consentArtefacts'][0]['id'] ?? null;

            $consent = AbdmConsent::where('request_id', $requestId)->first();
            if (!$consent) {
                Log::warning('AbdmService.onConsentRequest: Consent record not found', [
                    'request_id' => $requestId,
                ]);
                return;
            }

            if ($consentStatus === 'GRANTED') {
                $consent->update([
                    'consent_id' => $consentArtefactId,
                    'status' => 'GRANTED',
                ]);
                $consent->patient()->update(['abdm_consent_active' => true]);
            } else {
                $consent->update(['status' => $consentStatus]);
            }

            Log::info('AbdmService.onConsentRequest: Processed', ['status' => $consentStatus]);
        } catch (\Exception $e) {
            Log::error('AbdmService.onConsentRequest: Failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Fetch a consent artefact from ABDM
     */
    public function fetchConsentArtefact(string $consentId): array
    {
        Log::info('AbdmService.fetchConsentArtefact', ['consentId' => $consentId]);

        try {
            $requestId = Str::uuid()->toString();

            $response = $this->callAbdmApi('POST', '/v0.5/consents/fetch', [
                'requestId' => $requestId,
                'timestamp' => now()->toIso8601String(),
                'consentId' => $consentId,
            ], ['X-CM-ID' => 'sbx']);

            return ['success' => true, 'data' => $response];
        } catch (\Exception $e) {
            Log::error('AbdmService.fetchConsentArtefact: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Share health information based on an approved consent
     */
    public function shareHealthInfo(string $consentId, array $dateRange): array
    {
        Log::info('AbdmService.shareHealthInfo', ['consentId' => $consentId]);

        try {
            $consent = AbdmConsent::where('consent_id', $consentId)
                ->where('status', 'GRANTED')
                ->firstOrFail();

            // Get all care contexts for this patient within the date range
            $careContexts = AbdmCareContext::where('patient_id', $consent->patient_id)
                ->where('clinic_id', $consent->clinic_id)
                ->whereHas('visit', function ($q) use ($dateRange) {
                    $q->whereBetween('created_at', [
                        $dateRange['from'] ?? now()->subYears(5),
                        $dateRange['to'] ?? now(),
                    ]);
                })
                ->with('visit')
                ->get();

            $records = [];
            foreach ($careContexts as $context) {
                if ($context->visit) {
                    $records[] = $this->buildFhirBundle($context->visit);
                }
            }

            Log::info('AbdmService.shareHealthInfo: Shared', ['records_count' => count($records)]);

            return [
                'success' => true,
                'records_count' => count($records),
                'data' => $records,
            ];
        } catch (\Exception $e) {
            Log::error('AbdmService.shareHealthInfo: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─── M3: Health Information User (HIU) — Pull Records ─────────────────────

    /**
     * Request consent from a patient (HIU flow)
     */
    public function requestConsent(Patient $patient, string $purpose, array $hiTypes): array
    {
        Log::info('AbdmService.requestConsent', [
            'patient_id' => $patient->id,
            'purpose' => $purpose,
        ]);

        if (!$patient->abha_address) {
            return ['success' => false, 'error' => 'Patient has no ABHA address'];
        }

        try {
            $clinic = Clinic::findOrFail($patient->clinic_id);
            $requestId = Str::uuid()->toString();
            $timestamp = now()->toIso8601String();

            $purposeMap = [
                'CAREMGT' => ['text' => 'Care Management', 'code' => 'CAREMGT'],
                'BTG' => ['text' => 'Break the Glass', 'code' => 'BTG'],
                'PUBHLTH' => ['text' => 'Public Health', 'code' => 'PUBHLTH'],
                'HPAYMT' => ['text' => 'Healthcare Payment', 'code' => 'HPAYMT'],
                'DSRCH' => ['text' => 'Disease Specific Research', 'code' => 'DSRCH'],
            ];

            $purposeData = $purposeMap[$purpose] ?? $purposeMap['CAREMGT'];

            $payload = [
                'requestId' => $requestId,
                'timestamp' => $timestamp,
                'consent' => [
                    'purpose' => [
                        'text' => $purposeData['text'],
                        'code' => $purposeData['code'],
                        'refUri' => "https://abdm.gov.in/consent/purposes/{$purposeData['code']}",
                    ],
                    'patient' => [
                        'id' => $patient->abha_address,
                    ],
                    'hiu' => [
                        'id' => $clinic->hfr_id,
                    ],
                    'requester' => [
                        'name' => $clinic->name,
                        'identifier' => [
                            'type' => 'HFR',
                            'value' => $clinic->hfr_id,
                            'system' => 'https://hfr.abdm.gov.in/',
                        ],
                    ],
                    'hiTypes' => $hiTypes,
                    'permission' => [
                        'accessMode' => 'VIEW',
                        'dateRange' => [
                            'from' => now()->subYears(5)->toIso8601String(),
                            'to' => now()->toIso8601String(),
                        ],
                        'dataEraseAt' => now()->addDays(30)->toIso8601String(),
                        'frequency' => [
                            'unit' => 'HOUR',
                            'value' => 1,
                            'repeats' => 0,
                        ],
                    ],
                ],
            ];

            $response = $this->callAbdmApi('POST', '/v0.5/consent-requests/init', $payload, [
                'X-CM-ID' => 'sbx',
            ]);

            AbdmConsent::create([
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'request_id' => $requestId,
                'consent_id' => null,
                'purpose' => $purpose,
                'hi_types' => json_encode($hiTypes),
                'status' => 'REQUESTED',
                'valid_from' => now()->subYears(5),
                'valid_to' => now(),
                'expire_at' => now()->addDays(30),
            ]);

            Log::info('AbdmService.requestConsent: Sent', ['request_id' => $requestId]);

            return [
                'success' => true,
                'request_id' => $requestId,
                'status' => 'REQUESTED',
            ];
        } catch (\Exception $e) {
            Log::error('AbdmService.requestConsent: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch health records after consent is granted (HIU pull)
     */
    public function fetchHealthRecords(string $consentId): array
    {
        Log::info('AbdmService.fetchHealthRecords', ['consentId' => $consentId]);

        try {
            $consent = AbdmConsent::where('consent_id', $consentId)
                ->where('status', 'GRANTED')
                ->firstOrFail();

            $requestId = Str::uuid()->toString();

            $response = $this->callAbdmApi('POST', '/v0.5/health-information/cm/request', [
                'requestId' => $requestId,
                'timestamp' => now()->toIso8601String(),
                'hiRequest' => [
                    'consent' => [
                        'id' => $consentId,
                    ],
                    'dateRange' => [
                        'from' => $consent->valid_from->toIso8601String(),
                        'to' => $consent->valid_to->toIso8601String(),
                    ],
                    'dataPushUrl' => config('app.url') . '/api/v1/abdm/callback/health-info',
                ],
            ], ['X-CM-ID' => 'sbx']);

            return ['success' => true, 'request_id' => $requestId, 'data' => $response];
        } catch (\Exception $e) {
            Log::error('AbdmService.fetchHealthRecords: Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Make an authenticated API call to ABDM
     */
    private function callAbdmApi(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $token = $this->accessToken ?? $this->getAccessToken();

        $defaultHeaders = [
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $mergedHeaders = array_merge($defaultHeaders, $headers);

        $url = $this->baseUrl . $endpoint;

        Log::debug('AbdmService.callAbdmApi', [
            'method' => $method,
            'url' => $url,
        ]);

        $response = match (strtoupper($method)) {
            'POST' => Http::withHeaders($mergedHeaders)->post($url, $data),
            'GET' => Http::withHeaders($mergedHeaders)->get($url, $data),
            'PUT' => Http::withHeaders($mergedHeaders)->put($url, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $errorBody = $response->json();
        $errorMessage = $errorBody['error']['message']
            ?? $errorBody['message']
            ?? "ABDM API error: HTTP {$response->status()}";

        Log::error('AbdmService.callAbdmApi: API error', [
            'status' => $response->status(),
            'body' => $errorBody,
            'endpoint' => $endpoint,
        ]);

        throw new \RuntimeException($errorMessage);
    }

    /**
     * Encrypt data with ABDM's public key (RSA/OAEP)
     */
    private function encryptWithPublicKey(string $data): string
    {
        $publicKeyPath = config('services.abdm.public_key_path');

        if ($publicKeyPath && file_exists($publicKeyPath)) {
            $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));
            if ($publicKey) {
                openssl_public_encrypt($data, $encrypted, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);
                return base64_encode($encrypted);
            }
        }

        // Fallback: base64 encode (for sandbox/testing)
        Log::warning('AbdmService.encryptWithPublicKey: Using base64 fallback (no RSA key configured)');
        return base64_encode($data);
    }

    /**
     * Get Health Information Types for UI display
     */
    public static function getHiTypes(): array
    {
        return [
            'OPConsultation' => 'Outpatient Consultation',
            'Prescription' => 'Prescription',
            'DiagnosticReport' => 'Diagnostic Report',
            'ImmunizationRecord' => 'Immunization Record',
            'DischargeSummary' => 'Discharge Summary',
            'HealthDocumentRecord' => 'Health Document',
            'WellnessRecord' => 'Wellness Record',
        ];
    }
}
