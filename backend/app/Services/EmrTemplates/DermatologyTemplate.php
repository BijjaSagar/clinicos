<?php

namespace App\Services\EmrTemplates;

use Illuminate\Support\Facades\Log;

class DermatologyTemplate
{
    /**
     * Get dermatology EMR template fields
     */
    public static function getFields(): array
    {
        Log::info('Loading Dermatology EMR template');

        return [
            'specialty' => 'dermatology',
            'sections' => [
                // Chief Complaint
                [
                    'id' => 'chief_complaint',
                    'title' => 'Chief Complaint',
                    'fields' => [
                        [
                            'name' => 'chief_complaint',
                            'type' => 'textarea',
                            'label' => 'Chief Complaint',
                            'required' => true,
                            'placeholder' => 'Main presenting complaint...',
                        ],
                        [
                            'name' => 'duration',
                            'type' => 'text',
                            'label' => 'Duration',
                            'placeholder' => 'e.g., 2 weeks, 3 months',
                        ],
                        [
                            'name' => 'onset',
                            'type' => 'select',
                            'label' => 'Onset',
                            'options' => ['sudden', 'gradual', 'recurrent'],
                        ],
                        [
                            'name' => 'progression',
                            'type' => 'select',
                            'label' => 'Progression',
                            'options' => ['increasing', 'decreasing', 'static', 'fluctuating'],
                        ],
                    ],
                ],

                // History
                [
                    'id' => 'history',
                    'title' => 'History',
                    'fields' => [
                        [
                            'name' => 'presenting_history',
                            'type' => 'textarea',
                            'label' => 'History of Present Illness',
                        ],
                        [
                            'name' => 'associated_symptoms',
                            'type' => 'multiselect',
                            'label' => 'Associated Symptoms',
                            'options' => [
                                'itching', 'pain', 'burning', 'scaling',
                                'bleeding', 'discharge', 'swelling', 'fever',
                                'joint_pain', 'hair_loss', 'nail_changes',
                            ],
                        ],
                        [
                            'name' => 'aggravating_factors',
                            'type' => 'multiselect',
                            'label' => 'Aggravating Factors',
                            'options' => [
                                'sun_exposure', 'stress', 'heat', 'sweating',
                                'food', 'cosmetics', 'soaps', 'medications',
                            ],
                        ],
                        [
                            'name' => 'previous_treatment',
                            'type' => 'textarea',
                            'label' => 'Previous Treatment',
                        ],
                        [
                            'name' => 'family_history',
                            'type' => 'textarea',
                            'label' => 'Family History of Skin Disease',
                        ],
                    ],
                ],

                // Examination - Body Map
                [
                    'id' => 'examination',
                    'title' => 'Cutaneous Examination',
                    'type' => 'body_map',
                    'fields' => [
                        [
                            'name' => 'body_map_enabled',
                            'type' => 'boolean',
                            'label' => 'Use Body Map',
                            'default' => true,
                        ],
                        [
                            'name' => 'distribution',
                            'type' => 'select',
                            'label' => 'Distribution',
                            'options' => [
                                'localised', 'generalised', 'symmetrical',
                                'asymmetrical', 'acral', 'truncal', 'flexural',
                                'extensors', 'photodistributed', 'dermatomal',
                            ],
                        ],
                        [
                            'name' => 'arrangement',
                            'type' => 'select',
                            'label' => 'Arrangement',
                            'options' => [
                                'discrete', 'grouped', 'confluent', 'linear',
                                'annular', 'arcuate', 'polycyclic', 'reticular',
                            ],
                        ],
                    ],
                ],

                // Lesion Morphology
                [
                    'id' => 'lesion_morphology',
                    'title' => 'Lesion Morphology',
                    'fields' => [
                        [
                            'name' => 'primary_lesion',
                            'type' => 'select',
                            'label' => 'Primary Lesion',
                            'options' => [
                                'macule', 'patch', 'papule', 'plaque',
                                'nodule', 'tumour', 'vesicle', 'bulla',
                                'pustule', 'wheal', 'cyst', 'comedo',
                            ],
                        ],
                        [
                            'name' => 'secondary_changes',
                            'type' => 'multiselect',
                            'label' => 'Secondary Changes',
                            'options' => [
                                'scale', 'crust', 'erosion', 'ulcer',
                                'fissure', 'excoriation', 'lichenification',
                                'atrophy', 'scar', 'keloid', 'hyperpigmentation',
                                'hypopigmentation',
                            ],
                        ],
                        [
                            'name' => 'colour',
                            'type' => 'multiselect',
                            'label' => 'Colour',
                            'options' => [
                                'erythematous', 'hyperpigmented', 'hypopigmented',
                                'violaceous', 'yellowish', 'white', 'brown', 'black',
                            ],
                        ],
                        [
                            'name' => 'surface',
                            'type' => 'select',
                            'label' => 'Surface',
                            'options' => [
                                'smooth', 'rough', 'warty', 'scaly',
                                'crusted', 'ulcerated', 'macerated',
                            ],
                        ],
                        [
                            'name' => 'border',
                            'type' => 'select',
                            'label' => 'Border',
                            'options' => [
                                'well_defined', 'ill_defined', 'regular', 'irregular',
                                'raised', 'sloping', 'punched_out',
                            ],
                        ],
                    ],
                ],

                // Scales (PASI, IGA, DLQI)
                [
                    'id' => 'scales',
                    'title' => 'Grading Scales',
                    'fields' => [
                        [
                            'name' => 'pasi_enabled',
                            'type' => 'boolean',
                            'label' => 'Calculate PASI',
                            'condition' => ['diagnosis_category', 'contains', 'psoriasis'],
                        ],
                        [
                            'name' => 'pasi_score',
                            'type' => 'pasi_calculator',
                            'label' => 'PASI Score',
                            'components' => [
                                'head' => ['area' => 0, 'erythema' => 0, 'induration' => 0, 'desquamation' => 0],
                                'trunk' => ['area' => 0, 'erythema' => 0, 'induration' => 0, 'desquamation' => 0],
                                'upper_extremities' => ['area' => 0, 'erythema' => 0, 'induration' => 0, 'desquamation' => 0],
                                'lower_extremities' => ['area' => 0, 'erythema' => 0, 'induration' => 0, 'desquamation' => 0],
                            ],
                        ],
                        [
                            'name' => 'iga_score',
                            'type' => 'select',
                            'label' => 'IGA Score',
                            'options' => [
                                ['value' => 0, 'label' => '0 - Clear'],
                                ['value' => 1, 'label' => '1 - Almost Clear'],
                                ['value' => 2, 'label' => '2 - Mild'],
                                ['value' => 3, 'label' => '3 - Moderate'],
                                ['value' => 4, 'label' => '4 - Severe'],
                            ],
                        ],
                        [
                            'name' => 'dlqi_enabled',
                            'type' => 'boolean',
                            'label' => 'Calculate DLQI',
                        ],
                        [
                            'name' => 'dlqi_score',
                            'type' => 'dlqi_questionnaire',
                            'label' => 'DLQI Score',
                            'range' => [0, 30],
                        ],
                    ],
                ],

                // Diagnosis
                [
                    'id' => 'diagnosis',
                    'title' => 'Diagnosis',
                    'fields' => [
                        [
                            'name' => 'provisional_diagnosis',
                            'type' => 'text',
                            'label' => 'Provisional Diagnosis',
                            'required' => true,
                            'autocomplete' => 'icd10_dermatology',
                        ],
                        [
                            'name' => 'differential_diagnosis',
                            'type' => 'tags',
                            'label' => 'Differential Diagnosis',
                        ],
                        [
                            'name' => 'icd_code',
                            'type' => 'text',
                            'label' => 'ICD-10 Code',
                            'placeholder' => 'e.g., L40.0',
                        ],
                    ],
                ],

                // Procedures
                [
                    'id' => 'procedures',
                    'title' => 'Procedures',
                    'type' => 'repeatable',
                    'fields' => [
                        [
                            'name' => 'procedure_type',
                            'type' => 'select',
                            'label' => 'Procedure Type',
                            'options' => [
                                'chemical_peel' => 'Chemical Peel',
                                'laser_q_switch' => 'Q-Switch Laser',
                                'laser_co2' => 'CO2 Laser',
                                'laser_ipl' => 'IPL',
                                'laser_hair_removal' => 'Laser Hair Removal',
                                'prp' => 'PRP',
                                'mesotherapy' => 'Mesotherapy',
                                'botox' => 'Botox',
                                'fillers' => 'Dermal Fillers',
                                'microneedling' => 'Microneedling',
                                'cryotherapy' => 'Cryotherapy',
                                'biopsy' => 'Skin Biopsy',
                                'excision' => 'Excision',
                                'electrocautery' => 'Electrocautery',
                                'patch_test' => 'Patch Test',
                            ],
                        ],
                        [
                            'name' => 'procedure_details',
                            'type' => 'object',
                            'label' => 'Details',
                            'fields' => [
                                ['name' => 'agent', 'type' => 'text', 'label' => 'Agent/Device'],
                                ['name' => 'concentration', 'type' => 'text', 'label' => 'Concentration'],
                                ['name' => 'settings', 'type' => 'text', 'label' => 'Settings'],
                                ['name' => 'areas', 'type' => 'tags', 'label' => 'Treatment Areas'],
                                ['name' => 'session_number', 'type' => 'number', 'label' => 'Session #'],
                                ['name' => 'total_sessions', 'type' => 'number', 'label' => 'Total Sessions'],
                            ],
                        ],
                        [
                            'name' => 'post_procedure_care',
                            'type' => 'textarea',
                            'label' => 'Post-Procedure Instructions',
                        ],
                    ],
                ],

                // Prescription
                [
                    'id' => 'prescription',
                    'title' => 'Prescription',
                    'type' => 'prescription',
                    'common_drugs' => [
                        // Topical
                        ['name' => 'Adapalene 0.1% Gel', 'category' => 'Retinoid', 'form' => 'gel'],
                        ['name' => 'Tretinoin 0.025% Cream', 'category' => 'Retinoid', 'form' => 'cream'],
                        ['name' => 'Benzoyl Peroxide 2.5% Gel', 'category' => 'Anti-acne', 'form' => 'gel'],
                        ['name' => 'Clindamycin 1% Gel', 'category' => 'Antibiotic', 'form' => 'gel'],
                        ['name' => 'Mometasone 0.1% Cream', 'category' => 'Steroid', 'form' => 'cream'],
                        ['name' => 'Clobetasol 0.05% Ointment', 'category' => 'Steroid', 'form' => 'ointment'],
                        ['name' => 'Tacrolimus 0.1% Ointment', 'category' => 'Calcineurin Inhibitor', 'form' => 'ointment'],
                        ['name' => 'Hydroquinone 2% Cream', 'category' => 'Depigmenting', 'form' => 'cream'],
                        ['name' => 'Ketoconazole 2% Cream', 'category' => 'Antifungal', 'form' => 'cream'],
                        ['name' => 'Salicylic Acid 6% Ointment', 'category' => 'Keratolytic', 'form' => 'ointment'],
                        // Systemic
                        ['name' => 'Doxycycline 100mg', 'category' => 'Antibiotic', 'form' => 'capsule'],
                        ['name' => 'Azithromycin 500mg', 'category' => 'Antibiotic', 'form' => 'tablet'],
                        ['name' => 'Isotretinoin 20mg', 'category' => 'Retinoid', 'form' => 'capsule'],
                        ['name' => 'Methotrexate 7.5mg', 'category' => 'DMARD', 'form' => 'tablet'],
                        ['name' => 'Prednisolone 20mg', 'category' => 'Steroid', 'form' => 'tablet'],
                        ['name' => 'Hydroxyzine 25mg', 'category' => 'Antihistamine', 'form' => 'tablet'],
                        ['name' => 'Cetirizine 10mg', 'category' => 'Antihistamine', 'form' => 'tablet'],
                        ['name' => 'Fluconazole 150mg', 'category' => 'Antifungal', 'form' => 'tablet'],
                        ['name' => 'Itraconazole 100mg', 'category' => 'Antifungal', 'form' => 'capsule'],
                    ],
                ],

                // Plan
                [
                    'id' => 'plan',
                    'title' => 'Plan',
                    'fields' => [
                        [
                            'name' => 'advice',
                            'type' => 'textarea',
                            'label' => 'Advice/Instructions',
                            'placeholder' => 'Patient counselling, lifestyle advice...',
                        ],
                        [
                            'name' => 'investigations',
                            'type' => 'multiselect',
                            'label' => 'Investigations',
                            'options' => [
                                'skin_biopsy', 'koh_mount', 'tzanck_smear',
                                'patch_test', 'cbc', 'lft', 'kft', 'lipid_profile',
                                'hba1c', 'thyroid', 'ana', 'anti_dsdna', 'c3_c4',
                            ],
                        ],
                        [
                            'name' => 'referral',
                            'type' => 'text',
                            'label' => 'Referral To',
                        ],
                        [
                            'name' => 'followup_in_days',
                            'type' => 'select',
                            'label' => 'Follow-up',
                            'options' => [
                                ['value' => 7, 'label' => '1 Week'],
                                ['value' => 14, 'label' => '2 Weeks'],
                                ['value' => 21, 'label' => '3 Weeks'],
                                ['value' => 30, 'label' => '1 Month'],
                                ['value' => 60, 'label' => '2 Months'],
                                ['value' => 90, 'label' => '3 Months'],
                            ],
                        ],
                        [
                            'name' => 'next_procedure_date',
                            'type' => 'date',
                            'label' => 'Next Procedure Date',
                        ],
                    ],
                ],

                // Photos
                [
                    'id' => 'photos',
                    'title' => 'Clinical Photos',
                    'type' => 'photo_gallery',
                    'fields' => [
                        [
                            'name' => 'photo_consent',
                            'type' => 'boolean',
                            'label' => 'Photo consent obtained',
                            'required' => true,
                        ],
                        [
                            'name' => 'photos',
                            'type' => 'photo_upload',
                            'label' => 'Photos',
                            'tags' => ['before', 'after', 'progress', 'clinical'],
                            'body_regions' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Calculate PASI score
     */
    public static function calculatePasi(array $components): array
    {
        Log::info('Calculating PASI score', ['components' => $components]);

        $areaWeights = [
            'head' => 0.1,
            'trunk' => 0.3,
            'upper_extremities' => 0.2,
            'lower_extremities' => 0.4,
        ];

        $totalScore = 0;
        $regionScores = [];

        foreach ($components as $region => $data) {
            $area = $data['area'] ?? 0;
            $erythema = $data['erythema'] ?? 0;
            $induration = $data['induration'] ?? 0;
            $desquamation = $data['desquamation'] ?? 0;

            $regionScore = ($erythema + $induration + $desquamation) * $area * $areaWeights[$region];
            $regionScores[$region] = round($regionScore, 2);
            $totalScore += $regionScore;
        }

        $totalScore = round($totalScore, 1);

        // Interpretation
        $interpretation = match (true) {
            $totalScore == 0 => 'Clear',
            $totalScore <= 3 => 'Mild',
            $totalScore <= 10 => 'Moderate',
            $totalScore <= 20 => 'Severe',
            default => 'Very Severe',
        };

        Log::info('PASI calculated', [
            'score' => $totalScore,
            'interpretation' => $interpretation,
        ]);

        return [
            'total' => $totalScore,
            'regions' => $regionScores,
            'interpretation' => $interpretation,
        ];
    }

    /**
     * Get IGA interpretation
     */
    public static function getIgaInterpretation(int $score): string
    {
        return match ($score) {
            0 => 'Clear',
            1 => 'Almost Clear',
            2 => 'Mild',
            3 => 'Moderate',
            4 => 'Severe',
            default => 'Unknown',
        };
    }

    /**
     * Get DLQI interpretation
     */
    public static function getDlqiInterpretation(int $score): string
    {
        return match (true) {
            $score <= 1 => 'No effect on patient\'s life',
            $score <= 5 => 'Small effect on patient\'s life',
            $score <= 10 => 'Moderate effect on patient\'s life',
            $score <= 20 => 'Very large effect on patient\'s life',
            default => 'Extremely large effect on patient\'s life',
        };
    }

    /**
     * Get common diagnoses for autocomplete
     */
    public static function getCommonDiagnoses(): array
    {
        return [
            ['code' => 'L70.0', 'name' => 'Acne vulgaris'],
            ['code' => 'L70.1', 'name' => 'Acne conglobata'],
            ['code' => 'L20.9', 'name' => 'Atopic dermatitis'],
            ['code' => 'L40.0', 'name' => 'Psoriasis vulgaris'],
            ['code' => 'L40.1', 'name' => 'Generalized pustular psoriasis'],
            ['code' => 'L80', 'name' => 'Vitiligo'],
            ['code' => 'L81.0', 'name' => 'Post-inflammatory hyperpigmentation'],
            ['code' => 'L81.1', 'name' => 'Chloasma/Melasma'],
            ['code' => 'B35.0', 'name' => 'Tinea capitis'],
            ['code' => 'B35.4', 'name' => 'Tinea corporis'],
            ['code' => 'B35.6', 'name' => 'Tinea cruris'],
            ['code' => 'B36.0', 'name' => 'Pityriasis versicolor'],
            ['code' => 'L23.9', 'name' => 'Allergic contact dermatitis'],
            ['code' => 'L50.0', 'name' => 'Allergic urticaria'],
            ['code' => 'L57.0', 'name' => 'Actinic keratosis'],
            ['code' => 'L82', 'name' => 'Seborrheic keratosis'],
            ['code' => 'L91.0', 'name' => 'Keloid'],
            ['code' => 'B07', 'name' => 'Viral warts'],
            ['code' => 'L63.0', 'name' => 'Alopecia areata'],
            ['code' => 'L64.0', 'name' => 'Androgenetic alopecia'],
            ['code' => 'L30.3', 'name' => 'Infective dermatitis'],
            ['code' => 'L43.9', 'name' => 'Lichen planus'],
            ['code' => 'L42', 'name' => 'Pityriasis rosea'],
            ['code' => 'L71.0', 'name' => 'Rosacea'],
        ];
    }
}
