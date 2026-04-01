<?php

namespace App\Services;

use App\Models\Visit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Clinic;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FhirBuilder
{
    /**
     * Build a complete FHIR R4 Bundle from a visit
     */
    public function buildBundle(Visit $visit): array
    {
        $visit->loadMissing(['patient', 'doctor', 'clinic', 'prescriptions', 'lesions', 'scales']);

        Log::info('FhirBuilder.buildBundle: Building FHIR R4 bundle', ['visit_id' => $visit->id]);

        $entries = [
            $this->buildComposition($visit),
            $this->buildPatient($visit->patient),
            $this->buildPractitioner($visit->doctor),
            $this->buildOrganization($visit->clinic),
            $this->buildEncounter($visit),
        ];

        // Condition (diagnosis)
        if ($visit->diagnosis_text || $visit->diagnosis_code) {
            $entries[] = $this->buildCondition($visit);
        }

        // Medication requests from prescriptions
        if ($visit->prescriptions && $visit->prescriptions->count() > 0) {
            foreach ($this->buildMedicationRequests($visit) as $medicationEntry) {
                $entries[] = $medicationEntry;
            }
        }

        // Diagnostic reports (if structured data has labs)
        foreach ($this->buildDiagnosticReports($visit) as $reportEntry) {
            $entries[] = $reportEntry;
        }

        // Observations (vitals from structured data)
        foreach ($this->buildObservations($visit) as $observationEntry) {
            $entries[] = $observationEntry;
        }

        $bundle = [
            'resourceType' => 'Bundle',
            'id' => Str::uuid()->toString(),
            'meta' => [
                'lastUpdated' => now()->toIso8601String(),
                'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle'],
            ],
            'identifier' => [
                'system' => 'https://clinicos.in/fhir/bundle',
                'value' => "bundle-visit-{$visit->id}",
            ],
            'type' => 'document',
            'timestamp' => $visit->created_at->toIso8601String(),
            'entry' => $entries,
        ];

        Log::info('FhirBuilder.buildBundle: Complete', ['entries_count' => count($entries)]);

        return $bundle;
    }

    /**
     * Build Composition resource (document header)
     */
    private function buildComposition(Visit $visit): array
    {
        $patient = $visit->patient;
        $doctor = $visit->doctor;
        $clinic = $visit->clinic;

        $sections = [];

        if ($visit->chief_complaint) {
            $sections[] = [
                'title' => 'Chief Complaint',
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '10154-3',
                        'display' => 'Chief complaint',
                    ]],
                ],
                'text' => [
                    'status' => 'generated',
                    'div' => "<div xmlns=\"http://www.w3.org/1999/xhtml\">{$visit->chief_complaint}</div>",
                ],
            ];
        }

        if ($visit->diagnosis_text) {
            $sections[] = [
                'title' => 'Diagnosis',
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '29308-4',
                        'display' => 'Diagnosis',
                    ]],
                ],
                'text' => [
                    'status' => 'generated',
                    'div' => "<div xmlns=\"http://www.w3.org/1999/xhtml\">{$visit->diagnosis_text}" .
                        ($visit->diagnosis_code ? " ({$visit->diagnosis_code})" : '') . "</div>",
                ],
                'entry' => [
                    ['reference' => "Condition/condition-{$visit->id}"],
                ],
            ];
        }

        if ($visit->plan) {
            $sections[] = [
                'title' => 'Plan of Treatment',
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '18776-5',
                        'display' => 'Plan of care note',
                    ]],
                ],
                'text' => [
                    'status' => 'generated',
                    'div' => "<div xmlns=\"http://www.w3.org/1999/xhtml\">{$visit->plan}</div>",
                ],
            ];
        }

        if ($visit->prescriptions && $visit->prescriptions->count() > 0) {
            $sections[] = [
                'title' => 'Medications',
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '10160-0',
                        'display' => 'Medications',
                    ]],
                ],
                'entry' => $visit->prescriptions->map(function ($rx) {
                    return ['reference' => "MedicationRequest/med-{$rx->id}"];
                })->toArray(),
            ];
        }

        return [
            'fullUrl' => "urn:uuid:composition-{$visit->id}",
            'resource' => [
                'resourceType' => 'Composition',
                'id' => "composition-{$visit->id}",
                'status' => 'final',
                'type' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '34133-9',
                        'display' => 'Summarization of Episode Note',
                    ]],
                ],
                'subject' => [
                    'reference' => "Patient/{$patient->abha_id}",
                    'display' => $patient->name,
                ],
                'encounter' => [
                    'reference' => "Encounter/encounter-{$visit->id}",
                ],
                'date' => $visit->created_at->toIso8601String(),
                'author' => [[
                    'reference' => "Practitioner/" . ($doctor->hpr_id ?? "practitioner-{$doctor->id}"),
                    'display' => $doctor->name,
                ]],
                'title' => 'OPD Consultation Record',
                'custodian' => [
                    'reference' => "Organization/" . ($clinic->hfr_id ?? "org-{$clinic->id}"),
                    'display' => $clinic->name,
                ],
                'section' => $sections,
            ],
        ];
    }

    /**
     * Build Patient resource
     */
    private function buildPatient(Patient $patient): array
    {
        $genderMap = [
            'm' => 'male',
            'f' => 'female',
            'male' => 'male',
            'female' => 'female',
            'o' => 'other',
            'other' => 'other',
        ];

        return [
            'fullUrl' => "urn:uuid:patient-{$patient->id}",
            'resource' => [
                'resourceType' => 'Patient',
                'id' => $patient->abha_id ?? "patient-{$patient->id}",
                'meta' => [
                    'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Patient'],
                ],
                'identifier' => array_filter([
                    $patient->abha_id ? [
                        'type' => [
                            'coding' => [[
                                'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                'code' => 'MR',
                                'display' => 'Medical record number',
                            ]],
                        ],
                        'system' => 'https://healthid.ndhm.gov.in',
                        'value' => $patient->abha_id,
                    ] : null,
                ]),
                'name' => [[
                    'text' => $patient->name,
                    'use' => 'official',
                ]],
                'gender' => $genderMap[strtolower($patient->sex ?? '')] ?? 'unknown',
                'birthDate' => $patient->dob?->format('Y-m-d'),
                'telecom' => array_filter([
                    $patient->phone ? [
                        'system' => 'phone',
                        'value' => $patient->phone,
                        'use' => 'mobile',
                    ] : null,
                    $patient->email ? [
                        'system' => 'email',
                        'value' => $patient->email,
                    ] : null,
                ]),
                'address' => $patient->address ? [[
                    'text' => $patient->address,
                    'use' => 'home',
                ]] : [],
            ],
        ];
    }

    /**
     * Build Practitioner resource
     */
    private function buildPractitioner(User $doctor): array
    {
        return [
            'fullUrl' => "urn:uuid:practitioner-{$doctor->id}",
            'resource' => [
                'resourceType' => 'Practitioner',
                'id' => $doctor->hpr_id ?? "practitioner-{$doctor->id}",
                'meta' => [
                    'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Practitioner'],
                ],
                'identifier' => array_filter([
                    isset($doctor->hpr_id) ? [
                        'system' => 'https://hpr.abdm.gov.in',
                        'value' => $doctor->hpr_id,
                    ] : null,
                ]),
                'name' => [[
                    'text' => $doctor->name,
                    'use' => 'official',
                ]],
                'qualification' => isset($doctor->qualification) ? [[
                    'code' => [
                        'text' => $doctor->qualification,
                    ],
                ]] : [],
            ],
        ];
    }

    /**
     * Build Organization resource
     */
    private function buildOrganization(Clinic $clinic): array
    {
        return [
            'fullUrl' => "urn:uuid:org-{$clinic->id}",
            'resource' => [
                'resourceType' => 'Organization',
                'id' => $clinic->hfr_id ?? "org-{$clinic->id}",
                'meta' => [
                    'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Organization'],
                ],
                'identifier' => array_filter([
                    $clinic->hfr_id ? [
                        'system' => 'https://hfr.abdm.gov.in',
                        'value' => $clinic->hfr_id,
                    ] : null,
                ]),
                'name' => $clinic->name,
                'telecom' => array_filter([
                    $clinic->phone ? [
                        'system' => 'phone',
                        'value' => $clinic->phone,
                    ] : null,
                    $clinic->email ? [
                        'system' => 'email',
                        'value' => $clinic->email,
                    ] : null,
                ]),
                'address' => [[
                    'line' => array_filter([$clinic->address_line1, $clinic->address_line2]),
                    'city' => $clinic->city,
                    'state' => $clinic->state,
                    'postalCode' => $clinic->pincode,
                    'country' => 'IN',
                ]],
            ],
        ];
    }

    /**
     * Build Encounter resource
     */
    private function buildEncounter(Visit $visit): array
    {
        return [
            'fullUrl' => "urn:uuid:encounter-{$visit->id}",
            'resource' => [
                'resourceType' => 'Encounter',
                'id' => "encounter-{$visit->id}",
                'meta' => [
                    'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Encounter'],
                ],
                'status' => $visit->status === 'finalised' ? 'finished' : 'in-progress',
                'class' => [
                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                    'code' => 'AMB',
                    'display' => 'ambulatory',
                ],
                'subject' => [
                    'reference' => "Patient/{$visit->patient->abha_id}",
                    'display' => $visit->patient->name,
                ],
                'participant' => [[
                    'individual' => [
                        'reference' => "Practitioner/" . ($visit->doctor->hpr_id ?? "practitioner-{$visit->doctor->id}"),
                        'display' => $visit->doctor->name,
                    ],
                ]],
                'period' => [
                    'start' => ($visit->started_at ?? $visit->created_at)->toIso8601String(),
                    'end' => ($visit->finalised_at ?? $visit->updated_at)->toIso8601String(),
                ],
                'reasonCode' => $visit->chief_complaint ? [[
                    'text' => $visit->chief_complaint,
                ]] : [],
                'diagnosis' => ($visit->diagnosis_text || $visit->diagnosis_code) ? [[
                    'condition' => [
                        'reference' => "Condition/condition-{$visit->id}",
                    ],
                ]] : [],
                'serviceProvider' => [
                    'reference' => "Organization/" . ($visit->clinic->hfr_id ?? "org-{$visit->clinic->id}"),
                ],
            ],
        ];
    }

    /**
     * Build Condition (diagnosis) resource
     */
    private function buildCondition(Visit $visit): array
    {
        $coding = [];
        if ($visit->diagnosis_code) {
            $coding[] = [
                'system' => 'http://hl7.org/fhir/sid/icd-10',
                'code' => $visit->diagnosis_code,
                'display' => $visit->diagnosis_text,
            ];
        }

        return [
            'fullUrl' => "urn:uuid:condition-{$visit->id}",
            'resource' => [
                'resourceType' => 'Condition',
                'id' => "condition-{$visit->id}",
                'meta' => [
                    'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Condition'],
                ],
                'clinicalStatus' => [
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                        'code' => 'active',
                        'display' => 'Active',
                    ]],
                ],
                'verificationStatus' => [
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status',
                        'code' => 'confirmed',
                        'display' => 'Confirmed',
                    ]],
                ],
                'code' => [
                    'coding' => $coding,
                    'text' => $visit->diagnosis_text ?? 'Not specified',
                ],
                'subject' => [
                    'reference' => "Patient/{$visit->patient->abha_id}",
                ],
                'encounter' => [
                    'reference' => "Encounter/encounter-{$visit->id}",
                ],
                'recordedDate' => $visit->created_at->toIso8601String(),
            ],
        ];
    }

    /**
     * Build MedicationRequest resources from prescriptions
     */
    private function buildMedicationRequests(Visit $visit): array
    {
        $entries = [];

        foreach ($visit->prescriptions as $rx) {
            $entries[] = [
                'fullUrl' => "urn:uuid:med-{$rx->id}",
                'resource' => [
                    'resourceType' => 'MedicationRequest',
                    'id' => "med-{$rx->id}",
                    'meta' => [
                        'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/MedicationRequest'],
                    ],
                    'status' => 'active',
                    'intent' => 'order',
                    'medicationCodeableConcept' => [
                        'text' => $rx->drug_name ?? $rx->medicine_name ?? 'Unknown',
                    ],
                    'subject' => [
                        'reference' => "Patient/{$visit->patient->abha_id}",
                    ],
                    'encounter' => [
                        'reference' => "Encounter/encounter-{$visit->id}",
                    ],
                    'authoredOn' => $visit->created_at->toIso8601String(),
                    'requester' => [
                        'reference' => "Practitioner/" . ($visit->doctor->hpr_id ?? "practitioner-{$visit->doctor->id}"),
                    ],
                    'dosageInstruction' => [[
                        'text' => trim(implode(' ', array_filter([
                            $rx->dosage ?? null,
                            $rx->frequency ?? null,
                            $rx->duration ? "for {$rx->duration}" : null,
                            $rx->instructions ?? null,
                        ]))),
                        'timing' => [
                            'code' => [
                                'text' => $rx->frequency ?? 'As directed',
                            ],
                        ],
                        'route' => [
                            'text' => $rx->route ?? 'Oral',
                        ],
                    ]],
                    'note' => $rx->instructions ? [[
                        'text' => $rx->instructions,
                    ]] : [],
                ],
            ];
        }

        return $entries;
    }

    /**
     * Build DiagnosticReport resources
     */
    private function buildDiagnosticReports(Visit $visit): array
    {
        $entries = [];
        $structuredData = $visit->structured_data ?? [];

        $labResults = $structuredData['lab_results'] ?? $structuredData['investigations'] ?? [];

        foreach ($labResults as $index => $lab) {
            $reportId = "diag-{$visit->id}-{$index}";
            $entries[] = [
                'fullUrl' => "urn:uuid:{$reportId}",
                'resource' => [
                    'resourceType' => 'DiagnosticReport',
                    'id' => $reportId,
                    'meta' => [
                        'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DiagnosticReportLab'],
                    ],
                    'status' => 'final',
                    'code' => [
                        'text' => $lab['name'] ?? $lab['test_name'] ?? 'Laboratory Test',
                    ],
                    'subject' => [
                        'reference' => "Patient/{$visit->patient->abha_id}",
                    ],
                    'encounter' => [
                        'reference' => "Encounter/encounter-{$visit->id}",
                    ],
                    'effectiveDateTime' => $visit->created_at->toIso8601String(),
                    'conclusion' => $lab['result'] ?? $lab['value'] ?? null,
                ],
            ];
        }

        return $entries;
    }

    /**
     * Build Observation resources (vitals)
     */
    private function buildObservations(Visit $visit): array
    {
        $entries = [];
        $structuredData = $visit->structured_data ?? [];

        $vitals = $structuredData['vitals'] ?? [];

        $vitalsCoding = [
            'bp_systolic' => ['code' => '8480-6', 'display' => 'Systolic blood pressure', 'unit' => 'mmHg'],
            'bp_diastolic' => ['code' => '8462-4', 'display' => 'Diastolic blood pressure', 'unit' => 'mmHg'],
            'pulse' => ['code' => '8867-4', 'display' => 'Heart rate', 'unit' => '/min'],
            'heart_rate' => ['code' => '8867-4', 'display' => 'Heart rate', 'unit' => '/min'],
            'temperature' => ['code' => '8310-5', 'display' => 'Body temperature', 'unit' => 'degC'],
            'temp' => ['code' => '8310-5', 'display' => 'Body temperature', 'unit' => 'degC'],
            'spo2' => ['code' => '2708-6', 'display' => 'Oxygen saturation', 'unit' => '%'],
            'respiratory_rate' => ['code' => '9279-1', 'display' => 'Respiratory rate', 'unit' => '/min'],
            'rr' => ['code' => '9279-1', 'display' => 'Respiratory rate', 'unit' => '/min'],
            'weight' => ['code' => '29463-7', 'display' => 'Body weight', 'unit' => 'kg'],
            'height' => ['code' => '8302-2', 'display' => 'Body height', 'unit' => 'cm'],
            'bmi' => ['code' => '39156-5', 'display' => 'Body mass index', 'unit' => 'kg/m2'],
        ];

        $index = 0;
        foreach ($vitals as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $coding = $vitalsCoding[$key] ?? null;
            $obsId = "obs-{$visit->id}-{$index}";

            $entries[] = [
                'fullUrl' => "urn:uuid:{$obsId}",
                'resource' => [
                    'resourceType' => 'Observation',
                    'id' => $obsId,
                    'meta' => [
                        'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Observation'],
                    ],
                    'status' => 'final',
                    'category' => [[
                        'coding' => [[
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs',
                        ]],
                    ]],
                    'code' => [
                        'coding' => $coding ? [[
                            'system' => 'http://loinc.org',
                            'code' => $coding['code'],
                            'display' => $coding['display'],
                        ]] : [],
                        'text' => $coding['display'] ?? ucfirst(str_replace('_', ' ', $key)),
                    ],
                    'subject' => [
                        'reference' => "Patient/{$visit->patient->abha_id}",
                    ],
                    'encounter' => [
                        'reference' => "Encounter/encounter-{$visit->id}",
                    ],
                    'effectiveDateTime' => $visit->created_at->toIso8601String(),
                    'valueQuantity' => [
                        'value' => is_numeric($value) ? (float) $value : null,
                        'unit' => $coding['unit'] ?? '',
                        'system' => 'http://unitsofmeasure.org',
                        'code' => $coding['unit'] ?? '',
                    ],
                ],
            ];

            $index++;
        }

        return $entries;
    }
}
