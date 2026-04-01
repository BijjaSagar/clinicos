<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AbdmCareContext;
use App\Models\AbdmConsent;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * ABDM M2 - Health Information Provider (HIP) Controller
 * 
 * This handles the HIP functionality for sharing health records with patients
 * via the ABDM network. Clinics can:
 * - Link records to patient's ABHA
 * - Respond to consent requests
 * - Share health records (OPConsultation, DiagnosticReport, Prescription)
 */
class AbdmHipController extends Controller
{
    private string $baseUrl;
    private ?string $clientId;
    private ?string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.abdm.base_url', 'https://dev.abdm.gov.in');
        $this->clientId = config('services.abdm.client_id');
        $this->clientSecret = config('services.abdm.client_secret');
    }

    /**
     * Show HIP Dashboard
     */
    public function index(): View
    {
        Log::info('AbdmHipController: Loading HIP dashboard');

        $clinicId = auth()->user()->clinic_id;
        $clinic = auth()->user()->clinic;

        Log::info('AbdmHipController: clinic context', [
            'clinic_id' => $clinicId,
            'has_clinic_model' => $clinic !== null,
        ]);

        $stats = [
            'records_shared' => AbdmCareContext::where('clinic_id', $clinicId)->count(),
            'pending_consents' => AbdmConsent::where('clinic_id', $clinicId)
                ->where('status', AbdmConsent::STATUS_REQUESTED)
                ->count(),
            'linked_patients' => Patient::where('clinic_id', $clinicId)
                ->whereNotNull('abha_id')
                ->count(),
            'hip_registered' => $clinic !== null && !empty($clinic->hfr_id) && $clinic->abdm_m2_live,
        ];

        $recentConsents = AbdmConsent::with('patient')
            ->where('clinic_id', $clinicId)
            ->latest()
            ->limit(10)
            ->get();

        Log::info('AbdmHipController: HIP dashboard stats prepared', [
            'clinic_id' => $clinicId,
            'records_shared' => $stats['records_shared'],
            'pending_consents' => $stats['pending_consents'],
            'linked_patients' => $stats['linked_patients'],
            'recent_consents_count' => $recentConsents->count(),
        ]);

        return view('abdm.hip', compact('stats', 'recentConsents', 'clinic'));
    }

    /**
     * Register facility as HIP
     */
    public function registerHIP(Request $request): JsonResponse
    {
        Log::info('AbdmHipController: Registering as HIP');

        $clinic = auth()->user()->clinic;

        if (!$clinic->hfr_id) {
            return response()->json([
                'success' => false,
                'error' => 'Facility must be registered with HFR first',
            ], 400);
        }

        try {
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                return response()->json(['success' => false, 'error' => 'Could not authenticate with ABDM'], 500);
            }

            // Register HIP bridge
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-CM-ID' => 'sbx',
            ])->post($this->baseUrl . '/v1/bridges', [
                'id' => 'HIP_' . $clinic->id,
                'name' => $clinic->name,
                'type' => 'HIP',
                'url' => config('app.url') . '/api/abdm/hip',
                'active' => true,
                'blocklisted' => false,
            ]);

            if ($response->successful()) {
                $clinic->update([
                    'abdm_m2_live' => true,
                    'hip_id' => 'HIP_' . $clinic->id,
                ]);

                Log::info('AbdmHipController: HIP registered', ['clinic_id' => $clinic->id]);

                return response()->json([
                    'success' => true,
                    'message' => 'Registered as Health Information Provider successfully',
                ]);
            }

            Log::error('AbdmHipController: HIP registration failed', ['response' => $response->body()]);
            return response()->json(['success' => false, 'error' => 'Registration failed'], 500);
        } catch (\Throwable $e) {
            Log::error('AbdmHipController: HIP registration error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Link care context (patient record) to ABHA
     */
    public function linkCareContext(Request $request, Patient $patient): JsonResponse
    {
        Log::info('AbdmHipController: Linking care context', ['patient_id' => $patient->id]);

        $clinicId = auth()->user()->clinic_id;
        abort_unless($patient->clinic_id === $clinicId, 403);

        $validated = $request->validate([
            'visit_id' => 'nullable|exists:visits,id',
            'care_context_type' => 'required|in:OPConsultation,DiagnosticReport,Prescription,DischargeSummary',
        ]);

        if (!$patient->abha_id) {
            return response()->json([
                'success' => false,
                'error' => 'Patient does not have ABHA ID linked',
            ], 400);
        }

        $clinic = auth()->user()->clinic;

        try {
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                return response()->json(['success' => false, 'error' => 'Authentication failed'], 500);
            }

            $careContextReference = 'CC_' . ($validated['visit_id'] ?? $patient->id) . '_' . time();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-CM-ID' => 'sbx',
            ])->post($this->baseUrl . '/v0.5/links/link/add-contexts', [
                'link' => [
                    'accessToken' => $accessToken,
                    'patient' => [
                        'referenceNumber' => 'PAT_' . $patient->id,
                        'display' => $patient->name,
                        'careContexts' => [
                            [
                                'referenceNumber' => $careContextReference,
                                'display' => $validated['care_context_type'] . ' - ' . now()->format('d M Y'),
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $careContext = AbdmCareContext::updateOrCreate(
                    ['care_context_reference' => $careContextReference],
                    [
                        'patient_id' => $patient->id,
                        'clinic_id' => $patient->clinic_id,
                        'visit_id' => $validated['visit_id'] ?? null,
                        'display_name' => $validated['care_context_type'] . ' - ' . now()->format('d M Y'),
                        'hi_type' => $validated['care_context_type'],
                        'fhir_resource_type' => $validated['care_context_type'],
                        'status' => AbdmCareContext::STATUS_ACTIVE,
                    ]
                );

                Log::info('AbdmHipController: Care context linked and persisted', [
                    'reference' => $careContextReference,
                    'care_context_id' => $careContext->id,
                    'patient_id' => $patient->id,
                    'visit_id' => $validated['visit_id'] ?? null,
                    'hi_type' => $validated['care_context_type'],
                ]);

                return response()->json([
                    'success' => true,
                    'care_context_reference' => $careContextReference,
                    'care_context_id' => $careContext->id,
                    'message' => 'Care context linked successfully',
                ]);
            }

            return response()->json(['success' => false, 'error' => 'Linking failed'], 500);
        } catch (\Throwable $e) {
            Log::error('AbdmHipController: Link error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle consent request notification (webhook from ABDM)
     */
    public function handleConsentRequest(Request $request): JsonResponse
    {
        Log::info('AbdmHipController: Consent request received', ['data' => $request->all()]);

        $consentRequestId = $request->input('consentRequestId')
            ?? $request->input('consentRequest.id')
            ?? $request->input('notification.consentRequestId')
            ?? $request->input('requestId');
        $patientRef = $request->input('patient.id')
            ?? $request->input('patient.referenceNumber')
            ?? $request->input('consent.patient.id')
            ?? $request->input('notification.patient.id');
        $purpose = $request->input('purpose.code')
            ?? $request->input('consent.purpose.code')
            ?? 'CAREMGT';
        $hiTypes = $request->input('hiTypes')
            ?? $request->input('consent.hiTypes')
            ?? [];
        $dateFrom = $request->input('permission.dateRange.from')
            ?? $request->input('consent.permission.dateRange.from');
        $dateTo = $request->input('permission.dateRange.to')
            ?? $request->input('consent.permission.dateRange.to');

        if (!$consentRequestId) {
            Log::warning('AbdmHipController: Consent request missing consentRequestId');
            return response()->json([
                'status' => 'NACK',
                'message' => 'consentRequestId missing',
            ], 422);
        }

        try {
            $resolvedPatient = $this->resolvePatientForConsent($patientRef);

            if (!$resolvedPatient) {
                Log::warning('AbdmHipController: Could not resolve patient for consent request', [
                    'consent_request_id' => $consentRequestId,
                    'patient_ref' => $patientRef,
                ]);
                return response()->json([
                    'status' => 'NACK',
                    'message' => 'patient not found',
                ], 404);
            }

            $consent = AbdmConsent::updateOrCreate(
                ['consent_request_id' => $consentRequestId],
                [
                    'clinic_id' => $resolvedPatient->clinic_id,
                    'patient_id' => $resolvedPatient->id,
                    'status' => AbdmConsent::STATUS_REQUESTED,
                    'purpose' => $purpose,
                    'hi_types' => is_array($hiTypes) ? $hiTypes : [$hiTypes],
                    'date_from' => $this->safeParseDate($dateFrom),
                    'date_to' => $this->safeParseDate($dateTo),
                ]
            );

            Log::info('AbdmHipController: Consent request persisted', [
                'consent_id' => $consent->id,
                'consent_request_id' => $consentRequestId,
                'patient_id' => $resolvedPatient->id,
                'clinic_id' => $resolvedPatient->clinic_id,
                'purpose' => $purpose,
                'hi_types' => $consent->hi_types,
            ]);
        } catch (\Throwable $e) {
            Log::error('AbdmHipController: Failed to persist consent request', [
                'consent_request_id' => $consentRequestId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'status' => 'NACK',
                'message' => 'failed to persist consent request',
            ], 500);
        }
        
        return response()->json([
            'status' => 'ACK',
            'message' => 'Consent request acknowledged',
        ]);
    }

    /**
     * Respond to consent request (approve/deny)
     */
    public function respondToConsent(Request $request): JsonResponse
    {
        Log::info('AbdmHipController: Responding to consent');

        $validated = $request->validate([
            'consent_request_id' => 'required|string',
            'action' => 'required|in:approve,deny',
            'care_contexts' => 'required_if:action,approve|array',
        ]);

        try {
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                return response()->json(['success' => false, 'error' => 'Authentication failed'], 500);
            }

            if ($validated['action'] === 'approve') {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'X-CM-ID' => 'sbx',
                ])->post($this->baseUrl . '/v0.5/consents/hip/notify', [
                    'notification' => [
                        'consentRequestId' => $validated['consent_request_id'],
                        'status' => 'GRANTED',
                        'careContexts' => $validated['care_contexts'],
                    ],
                ]);

                if ($response->successful()) {
                    $consent = AbdmConsent::where('consent_request_id', $validated['consent_request_id'])->first();
                    if ($consent) {
                        $expiresAt = $consent->date_to
                            ? Carbon::parse($consent->date_to)->endOfDay()
                            : null;

                        $consent->update([
                            'status' => AbdmConsent::STATUS_GRANTED,
                            'granted_at' => now(),
                            'expires_at' => $expiresAt,
                        ]);
                        $consent->patient?->update(['abdm_consent_active' => true]);

                        Log::info('AbdmHipController: Consent approved and persisted', [
                            'consent_id' => $consent->id,
                            'consent_request_id' => $validated['consent_request_id'],
                            'patient_id' => $consent->patient_id,
                            'expires_at' => $expiresAt?->toIso8601String(),
                        ]);
                    } else {
                        Log::warning('AbdmHipController: Consent approval succeeded upstream but local consent record missing', [
                            'consent_request_id' => $validated['consent_request_id'],
                        ]);
                    }
                }
            } else {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'X-CM-ID' => 'sbx',
                ])->post($this->baseUrl . '/v0.5/consents/hip/notify', [
                    'notification' => [
                        'consentRequestId' => $validated['consent_request_id'],
                        'status' => 'DENIED',
                    ],
                ]);

                if ($response->successful()) {
                    $consent = AbdmConsent::where('consent_request_id', $validated['consent_request_id'])->first();
                    if ($consent) {
                        $consent->update([
                            'status' => AbdmConsent::STATUS_DENIED,
                        ]);

                        $activeConsentExists = AbdmConsent::where('patient_id', $consent->patient_id)
                            ->where('status', AbdmConsent::STATUS_GRANTED)
                            ->where(function ($query) {
                                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                            })
                            ->exists();
                        if (!$activeConsentExists) {
                            $consent->patient?->update(['abdm_consent_active' => false]);
                        }

                        Log::info('AbdmHipController: Consent denied and persisted', [
                            'consent_id' => $consent->id,
                            'consent_request_id' => $validated['consent_request_id'],
                            'patient_id' => $consent->patient_id,
                            'patient_has_other_active_consents' => $activeConsentExists,
                        ]);
                    }
                }
            }

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Consent response sent',
                ]);
            }

            return response()->json(['success' => false, 'error' => 'Response failed'], 500);
        } catch (\Throwable $e) {
            Log::error('AbdmHipController: Consent response error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle data request (FHIR bundle)
     */
    public function handleDataRequest(Request $request): JsonResponse
    {
        Log::info('AbdmHipController: Data request received');

        $transactionId = $request->input('transactionId');
        $consentId = $request->input('consent.id');
        $careContextRefs = $request->input('careContexts', []);

        try {
            // Build FHIR bundle for requested care contexts
            $bundle = $this->buildFHIRBundle($careContextRefs);

            // Encrypt and send data
            // In production, this requires proper encryption as per ABDM specs

            return response()->json([
                'status' => 'ACK',
                'transactionId' => $transactionId,
            ]);
        } catch (\Throwable $e) {
            Log::error('AbdmHipController: Data request error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'ERROR', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate FHIR bundle for a visit
     */
    public function generateFHIRBundle(Visit $visit): JsonResponse
    {
        Log::info('AbdmHipController: Generating FHIR bundle', ['visit_id' => $visit->id]);

        abort_unless(auth()->user()->clinic_id === $visit->clinic_id, 403);

        try {
            $patient = $visit->patient;
            $clinic = auth()->user()->clinic;
            $doctor = $visit->doctor;

            $bundle = [
                'resourceType' => 'Bundle',
                'id' => 'bundle-' . $visit->id,
                'type' => 'collection',
                'timestamp' => now()->toIso8601String(),
                'entry' => [],
            ];

            // Patient resource
            $bundle['entry'][] = [
                'resource' => [
                    'resourceType' => 'Patient',
                    'id' => 'patient-' . $patient->id,
                    'identifier' => [
                        [
                            'system' => 'https://healthid.abdm.gov.in',
                            'value' => $patient->abha_id,
                        ],
                    ],
                    'name' => [
                        ['text' => $patient->name],
                    ],
                    'gender' => $patient->gender ?? 'unknown',
                    'birthDate' => $patient->dob?->format('Y-m-d'),
                ],
            ];

            // Encounter resource
            $bundle['entry'][] = [
                'resource' => [
                    'resourceType' => 'Encounter',
                    'id' => 'encounter-' . $visit->id,
                    'status' => 'finished',
                    'class' => [
                        'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                        'code' => 'AMB',
                        'display' => 'ambulatory',
                    ],
                    'subject' => [
                        'reference' => 'Patient/patient-' . $patient->id,
                    ],
                    'period' => [
                        'start' => $visit->created_at->toIso8601String(),
                        'end' => $visit->updated_at->toIso8601String(),
                    ],
                ],
            ];

            // Condition (Diagnosis)
            if ($visit->diagnosis) {
                $bundle['entry'][] = [
                    'resource' => [
                        'resourceType' => 'Condition',
                        'id' => 'condition-' . $visit->id,
                        'clinicalStatus' => [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                                    'code' => 'active',
                                ],
                            ],
                        ],
                        'code' => [
                            'text' => $visit->diagnosis,
                        ],
                        'subject' => [
                            'reference' => 'Patient/patient-' . $patient->id,
                        ],
                        'encounter' => [
                            'reference' => 'Encounter/encounter-' . $visit->id,
                        ],
                    ],
                ];
            }

            // DocumentReference (Clinical Notes)
            if ($visit->chief_complaint || $visit->history_of_present_illness || $visit->physical_examination) {
                $clinicalNotes = '';
                if ($visit->chief_complaint) {
                    $clinicalNotes .= "Chief Complaint: {$visit->chief_complaint}\n\n";
                }
                if ($visit->history_of_present_illness) {
                    $clinicalNotes .= "History: {$visit->history_of_present_illness}\n\n";
                }
                if ($visit->physical_examination) {
                    $clinicalNotes .= "Examination: {$visit->physical_examination}\n\n";
                }

                $bundle['entry'][] = [
                    'resource' => [
                        'resourceType' => 'DocumentReference',
                        'id' => 'docref-' . $visit->id,
                        'status' => 'current',
                        'type' => [
                            'coding' => [
                                [
                                    'system' => 'http://snomed.info/sct',
                                    'code' => '371530004',
                                    'display' => 'Clinical consultation report',
                                ],
                            ],
                        ],
                        'subject' => [
                            'reference' => 'Patient/patient-' . $patient->id,
                        ],
                        'content' => [
                            [
                                'attachment' => [
                                    'contentType' => 'text/plain',
                                    'data' => base64_encode($clinicalNotes),
                                ],
                            ],
                        ],
                    ],
                ];
            }

            Log::info('AbdmHipController: FHIR bundle generated', ['entries' => count($bundle['entry'])]);

            return response()->json([
                'success' => true,
                'bundle' => $bundle,
            ]);
        } catch (\Throwable $e) {
            Log::error('AbdmHipController: FHIR bundle error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Build FHIR bundle from care context references
     */
    private function buildFHIRBundle(array $careContextRefs): array
    {
        Log::info('AbdmHipController: Building FHIR bundle from care contexts', [
            'care_context_refs' => $careContextRefs,
        ]);

        $contexts = AbdmCareContext::whereIn('care_context_reference', $careContextRefs)->get();

        $bundle = [
            'resourceType' => 'Bundle',
            'type' => 'collection',
            'timestamp' => now()->toIso8601String(),
            'entry' => $contexts->map(function (AbdmCareContext $context) {
                return [
                    'resource' => [
                        'resourceType' => 'Basic',
                        'id' => 'carecontext-' . $context->id,
                        'code' => [
                            'text' => $context->hi_type,
                        ],
                        'subject' => [
                            'reference' => 'Patient/' . $context->patient_id,
                            'display' => $context->display_name,
                        ],
                        'created' => $context->created_at?->toIso8601String(),
                    ],
                ];
            })->toArray(),
        ];

        Log::info('AbdmHipController: FHIR bundle built from care contexts', [
            'entry_count' => count($bundle['entry']),
        ]);

        return $bundle;
    }

    private function resolvePatientForConsent(?string $patientRef): ?Patient
    {
        if (!$patientRef) {
            return null;
        }

        if (str_starts_with($patientRef, 'PAT_')) {
            $patientId = (int) str_replace('PAT_', '', $patientRef);
            return Patient::find($patientId);
        }

        if (ctype_digit($patientRef)) {
            $patientId = (int) $patientRef;
            $patientById = Patient::find($patientId);
            if ($patientById) {
                return $patientById;
            }
        }

        return Patient::where('abha_id', $patientRef)
            ->orWhere('abha_address', $patientRef)
            ->first();
    }

    private function safeParseDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            Log::warning('AbdmHipController: Failed to parse date', [
                'raw_value' => $value,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get ABDM access token
     */
    private function getAccessToken(): ?string
    {
        if (!$this->clientId || !$this->clientSecret) {
            Log::warning('AbdmHipController: ABDM credentials not configured');
            return null;
        }

        try {
            $response = Http::post($this->baseUrl . '/v0.5/sessions', [
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                return $response->json('accessToken');
            }

            Log::error('AbdmHipController: Auth failed', ['response' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('AbdmHipController: Auth error', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
