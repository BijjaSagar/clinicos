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
use Carbon\Carbon;

class AbdmService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $authUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.abdm.base_url', 'https://dev.abdm.gov.in');
        $this->clientId = config('services.abdm.client_id', '');
        $this->clientSecret = config('services.abdm.client_secret', '');
        $this->authUrl = config('services.abdm.auth_url', 'https://dev.abdm.gov.in/gateway/v0.5/sessions');

        Log::info('AbdmService initialized', [
            'base_url' => $this->baseUrl,
        ]);
    }

    /**
     * Get access token from ABDM Gateway
     */
    public function getAccessToken(): ?string
    {
        Log::info('AbdmService.getAccessToken: Fetching token');

        $cacheKey = 'abdm_access_token';
        
        if (Cache::has($cacheKey)) {
            Log::info('AbdmService.getAccessToken: Using cached token');
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::post($this->authUrl, [
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $token = $response->json('accessToken');
                $expiresIn = $response->json('expiresIn', 1800);
                
                Cache::put($cacheKey, $token, $expiresIn - 60);
                
                Log::info('AbdmService.getAccessToken: Token fetched successfully');
                return $token;
            }

            Log::error('AbdmService.getAccessToken: Failed to get token', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('AbdmService.getAccessToken: Exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verify ABHA ID (Health ID)
     */
    public function verifyAbhaId(string $abhaId): array
    {
        Log::info('AbdmService.verifyAbhaId: Verifying ABHA ID', ['abha_id' => $abhaId]);

        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Failed to authenticate with ABDM'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer $token",
                'X-CM-ID' => 'sbx',
            ])->post("{$this->baseUrl}/v1/phr/profile/link/profileDetails", [
                'healthId' => $abhaId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('AbdmService.verifyAbhaId: Verification successful');
                
                return [
                    'success' => true,
                    'data' => [
                        'abha_id' => $data['healthId'] ?? $abhaId,
                        'abha_address' => $data['healthIdNumber'] ?? null,
                        'name' => $data['name'] ?? null,
                        'gender' => $data['gender'] ?? null,
                        'dob' => $data['dateOfBirth'] ?? null,
                        'mobile' => $data['mobile'] ?? null,
                        'address' => $data['address'] ?? null,
                        'state' => $data['state'] ?? null,
                        'district' => $data['district'] ?? null,
                    ],
                ];
            }

            Log::warning('AbdmService.verifyAbhaId: Verification failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('error.message') ?? 'Verification failed',
            ];
        } catch (\Exception $e) {
            Log::error('AbdmService.verifyAbhaId: Exception', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Link ABHA ID to patient
     */
    public function linkAbhaToPatient(int $patientId, string $abhaId, string $abhaAddress): bool
    {
        Log::info('AbdmService.linkAbhaToPatient: Linking ABHA to patient', [
            'patient_id' => $patientId,
            'abha_id' => $abhaId,
        ]);

        try {
            $patient = Patient::findOrFail($patientId);
            
            $patient->update([
                'abha_id' => $abhaId,
                'abha_address' => $abhaAddress,
                'abdm_consent_active' => true,
            ]);

            Log::info('AbdmService.linkAbhaToPatient: ABHA linked successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('AbdmService.linkAbhaToPatient: Exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Request consent for health records access
     */
    public function requestConsent(int $clinicId, int $patientId, array $purposes): ?string
    {
        Log::info('AbdmService.requestConsent: Requesting consent', [
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'purposes' => $purposes,
        ]);

        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        $patient = Patient::findOrFail($patientId);
        $clinic = Clinic::findOrFail($clinicId);

        if (!$patient->abha_id) {
            Log::warning('AbdmService.requestConsent: Patient has no ABHA ID');
            return null;
        }

        $requestId = $this->generateRequestId();
        $timestamp = Carbon::now()->toIso8601String();

        try {
            $consentPayload = [
                'requestId' => $requestId,
                'timestamp' => $timestamp,
                'consent' => [
                    'purpose' => [
                        'text' => 'Care Management',
                        'code' => 'CAREMGT',
                        'refUri' => 'https://abdm.gov.in/consent/purposes/1',
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
                    'hiTypes' => $purposes,
                    'permission' => [
                        'accessMode' => 'VIEW',
                        'dateRange' => [
                            'from' => Carbon::now()->subYears(5)->toIso8601String(),
                            'to' => Carbon::now()->toIso8601String(),
                        ],
                        'dataEraseAt' => Carbon::now()->addDays(30)->toIso8601String(),
                        'frequency' => [
                            'unit' => 'HOUR',
                            'value' => 1,
                            'repeats' => 0,
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer $token",
                'X-CM-ID' => 'sbx',
            ])->post("{$this->baseUrl}/v0.5/consent-requests/init", $consentPayload);

            if ($response->successful()) {
                // Store consent request
                AbdmConsent::create([
                    'clinic_id' => $clinicId,
                    'patient_id' => $patientId,
                    'request_id' => $requestId,
                    'consent_id' => null,
                    'purpose' => json_encode($purposes),
                    'hi_types' => json_encode($purposes),
                    'status' => 'REQUESTED',
                    'valid_from' => Carbon::now()->subYears(5),
                    'valid_to' => Carbon::now(),
                    'expire_at' => Carbon::now()->addDays(30),
                ]);

                Log::info('AbdmService.requestConsent: Consent request sent', [
                    'request_id' => $requestId,
                ]);

                return $requestId;
            }

            Log::error('AbdmService.requestConsent: Failed to send consent request', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('AbdmService.requestConsent: Exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Handle consent callback from ABDM
     */
    public function handleConsentCallback(array $payload): bool
    {
        Log::info('AbdmService.handleConsentCallback: Processing callback', [
            'payload_keys' => array_keys($payload),
        ]);

        try {
            $requestId = $payload['requestId'] ?? null;
            $consentStatus = $payload['notification']['status'] ?? null;
            $consentArtefactId = $payload['notification']['consentArtefacts'][0]['id'] ?? null;

            $consent = AbdmConsent::where('request_id', $requestId)->first();
            if (!$consent) {
                Log::warning('AbdmService.handleConsentCallback: Consent not found', [
                    'request_id' => $requestId,
                ]);
                return false;
            }

            if ($consentStatus === 'GRANTED') {
                $consent->update([
                    'consent_id' => $consentArtefactId,
                    'status' => 'GRANTED',
                ]);

                // Update patient's ABDM consent status
                $consent->patient()->update(['abdm_consent_active' => true]);

                Log::info('AbdmService.handleConsentCallback: Consent granted', [
                    'consent_id' => $consentArtefactId,
                ]);
            } else {
                $consent->update(['status' => $consentStatus]);
                Log::info('AbdmService.handleConsentCallback: Consent status updated', [
                    'status' => $consentStatus,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('AbdmService.handleConsentCallback: Exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create care context for a visit
     */
    public function createCareContext(int $visitId): ?AbdmCareContext
    {
        Log::info('AbdmService.createCareContext: Creating care context', [
            'visit_id' => $visitId,
        ]);

        try {
            $visit = Visit::with(['patient', 'clinic', 'doctor'])->findOrFail($visitId);
            
            if (!$visit->patient->abha_id) {
                Log::warning('AbdmService.createCareContext: Patient has no ABHA ID');
                return null;
            }

            $referenceNumber = 'CC-' . $visit->clinic_id . '-' . $visit->id . '-' . time();
            
            $careContext = AbdmCareContext::create([
                'clinic_id' => $visit->clinic_id,
                'patient_id' => $visit->patient_id,
                'visit_id' => $visitId,
                'reference_number' => $referenceNumber,
                'display' => "Visit on " . $visit->created_at->format('d M Y') . " - " . $visit->diagnosis,
                'hi_type' => 'OPConsultation',
            ]);

            Log::info('AbdmService.createCareContext: Care context created', [
                'reference_number' => $referenceNumber,
            ]);

            return $careContext;
        } catch (\Exception $e) {
            Log::error('AbdmService.createCareContext: Exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Push health record to ABDM (FHIR format)
     */
    public function pushHealthRecord(int $visitId): bool
    {
        Log::info('AbdmService.pushHealthRecord: Pushing health record', [
            'visit_id' => $visitId,
        ]);

        $token = $this->getAccessToken();
        if (!$token) {
            return false;
        }

        try {
            $visit = Visit::with(['patient', 'clinic', 'doctor', 'prescriptions'])->findOrFail($visitId);
            
            if (!$visit->patient->abha_id) {
                Log::warning('AbdmService.pushHealthRecord: Patient has no ABHA ID');
                return false;
            }

            // Create care context if not exists
            $careContext = AbdmCareContext::where('visit_id', $visitId)->first();
            if (!$careContext) {
                $careContext = $this->createCareContext($visitId);
            }

            // Build FHIR Bundle
            $fhirBundle = $this->buildFhirBundle($visit);

            $requestId = $this->generateRequestId();
            $timestamp = Carbon::now()->toIso8601String();

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

            $response = Http::withHeaders([
                'Authorization' => "Bearer $token",
                'X-CM-ID' => 'sbx',
            ])->post("{$this->baseUrl}/v0.5/links/link/add-contexts", $payload);

            if ($response->successful()) {
                $careContext->update(['linked_at' => Carbon::now()]);
                
                Log::info('AbdmService.pushHealthRecord: Record pushed successfully');
                return true;
            }

            Log::error('AbdmService.pushHealthRecord: Failed to push record', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('AbdmService.pushHealthRecord: Exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Build FHIR R4 Bundle for a visit
     */
    protected function buildFhirBundle(Visit $visit): array
    {
        Log::info('AbdmService.buildFhirBundle: Building FHIR bundle', [
            'visit_id' => $visit->id,
        ]);

        $patient = $visit->patient;
        $doctor = $visit->doctor;
        $clinic = $visit->clinic;

        $bundle = [
            'resourceType' => 'Bundle',
            'id' => "bundle-visit-{$visit->id}",
            'type' => 'document',
            'timestamp' => Carbon::now()->toIso8601String(),
            'entry' => [],
        ];

        // Composition (document header)
        $bundle['entry'][] = [
            'resource' => [
                'resourceType' => 'Composition',
                'id' => "composition-{$visit->id}",
                'status' => 'final',
                'type' => [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => '34133-9',
                            'display' => 'Outpatient Note',
                        ],
                    ],
                ],
                'subject' => [
                    'reference' => "Patient/{$patient->abha_id}",
                ],
                'date' => $visit->created_at->toIso8601String(),
                'author' => [
                    [
                        'reference' => "Practitioner/{$doctor->hpr_id}",
                        'display' => $doctor->name,
                    ],
                ],
                'title' => 'OPD Consultation Note',
                'custodian' => [
                    'reference' => "Organization/{$clinic->hfr_id}",
                ],
                'section' => [
                    [
                        'title' => 'Chief Complaint',
                        'text' => [
                            'status' => 'generated',
                            'div' => "<div>{$visit->chief_complaint}</div>",
                        ],
                    ],
                    [
                        'title' => 'Diagnosis',
                        'text' => [
                            'status' => 'generated',
                            'div' => "<div>{$visit->diagnosis} ({$visit->icd_code})</div>",
                        ],
                    ],
                    [
                        'title' => 'Plan',
                        'text' => [
                            'status' => 'generated',
                            'div' => "<div>{$visit->plan}</div>",
                        ],
                    ],
                ],
            ],
        ];

        // Patient resource
        $bundle['entry'][] = [
            'resource' => [
                'resourceType' => 'Patient',
                'id' => $patient->abha_id,
                'identifier' => [
                    [
                        'system' => 'https://healthid.ndhm.gov.in',
                        'value' => $patient->abha_id,
                    ],
                ],
                'name' => [
                    [
                        'text' => $patient->name,
                    ],
                ],
                'gender' => strtolower($patient->sex) === 'f' ? 'female' : 'male',
                'birthDate' => $patient->dob?->format('Y-m-d'),
            ],
        ];

        // Practitioner resource
        $bundle['entry'][] = [
            'resource' => [
                'resourceType' => 'Practitioner',
                'id' => $doctor->hpr_id ?? "practitioner-{$doctor->id}",
                'identifier' => [
                    [
                        'system' => 'https://hpr.abdm.gov.in',
                        'value' => $doctor->hpr_id,
                    ],
                ],
                'name' => [
                    [
                        'text' => $doctor->name,
                    ],
                ],
                'qualification' => [
                    [
                        'code' => [
                            'text' => $doctor->qualification,
                        ],
                    ],
                ],
            ],
        ];

        // Organization resource
        $bundle['entry'][] = [
            'resource' => [
                'resourceType' => 'Organization',
                'id' => $clinic->hfr_id ?? "org-{$clinic->id}",
                'identifier' => [
                    [
                        'system' => 'https://hfr.abdm.gov.in',
                        'value' => $clinic->hfr_id,
                    ],
                ],
                'name' => $clinic->name,
                'address' => [
                    [
                        'city' => $clinic->city,
                        'state' => $clinic->state,
                    ],
                ],
            ],
        ];

        // Encounter (visit) resource
        $bundle['entry'][] = [
            'resource' => [
                'resourceType' => 'Encounter',
                'id' => "encounter-{$visit->id}",
                'status' => 'finished',
                'class' => [
                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                    'code' => 'AMB',
                    'display' => 'ambulatory',
                ],
                'subject' => [
                    'reference' => "Patient/{$patient->abha_id}",
                ],
                'participant' => [
                    [
                        'individual' => [
                            'reference' => "Practitioner/{$doctor->hpr_id}",
                        ],
                    ],
                ],
                'period' => [
                    'start' => $visit->created_at->toIso8601String(),
                    'end' => $visit->updated_at->toIso8601String(),
                ],
            ],
        ];

        // Condition (diagnosis) resource
        if ($visit->diagnosis) {
            $bundle['entry'][] = [
                'resource' => [
                    'resourceType' => 'Condition',
                    'id' => "condition-{$visit->id}",
                    'clinicalStatus' => [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                                'code' => 'active',
                            ],
                        ],
                    ],
                    'code' => [
                        'coding' => [
                            [
                                'system' => 'http://hl7.org/fhir/sid/icd-10',
                                'code' => $visit->icd_code,
                                'display' => $visit->diagnosis,
                            ],
                        ],
                        'text' => $visit->diagnosis,
                    ],
                    'subject' => [
                        'reference' => "Patient/{$patient->abha_id}",
                    ],
                    'encounter' => [
                        'reference' => "Encounter/encounter-{$visit->id}",
                    ],
                    'recordedDate' => $visit->created_at->toIso8601String(),
                ],
            ];
        }

        Log::info('AbdmService.buildFhirBundle: Bundle built successfully', [
            'entries_count' => count($bundle['entry']),
        ]);

        return $bundle;
    }

    /**
     * Handle data request from PHR app
     */
    public function handleDataRequest(array $payload): array
    {
        Log::info('AbdmService.handleDataRequest: Processing data request', [
            'payload_keys' => array_keys($payload),
        ]);

        try {
            $consentId = $payload['hiRequest']['consent']['id'] ?? null;
            $careContexts = $payload['hiRequest']['keyMaterial']['cryptoAlg'] ?? [];

            // Verify consent
            $consent = AbdmConsent::where('consent_id', $consentId)
                ->where('status', 'GRANTED')
                ->first();

            if (!$consent) {
                Log::warning('AbdmService.handleDataRequest: Invalid or expired consent');
                return ['success' => false, 'error' => 'Invalid consent'];
            }

            // Fetch and return requested health records
            $records = [];
            foreach ($careContexts as $context) {
                $careContext = AbdmCareContext::where('reference_number', $context['careContextReference'])->first();
                if ($careContext && $careContext->visit) {
                    $records[] = $this->buildFhirBundle($careContext->visit);
                }
            }

            Log::info('AbdmService.handleDataRequest: Data request processed', [
                'records_count' => count($records),
            ]);

            return ['success' => true, 'data' => $records];
        } catch (\Exception $e) {
            Log::error('AbdmService.handleDataRequest: Exception', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate unique request ID
     */
    protected function generateRequestId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Get HI Types (Health Information Types)
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
