<?php

namespace App\Services\EmrTemplates;

use Illuminate\Support\Facades\Log;

class PhysiotherapyTemplate
{
    /**
     * Get physiotherapy EMR template fields
     */
    public static function getFields(): array
    {
        Log::info('Loading Physiotherapy EMR template');

        return [
            'specialty' => 'physiotherapy',
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
                            'name' => 'pain_location',
                            'type' => 'multiselect',
                            'label' => 'Pain Location',
                            'options' => [
                                'neck', 'upper_back', 'lower_back', 'shoulder_left', 'shoulder_right',
                                'elbow_left', 'elbow_right', 'wrist_left', 'wrist_right',
                                'hip_left', 'hip_right', 'knee_left', 'knee_right',
                                'ankle_left', 'ankle_right', 'foot_left', 'foot_right',
                            ],
                        ],
                        [
                            'name' => 'duration',
                            'type' => 'text',
                            'label' => 'Duration',
                            'placeholder' => 'e.g., 2 weeks, 3 months',
                        ],
                        [
                            'name' => 'onset_type',
                            'type' => 'select',
                            'label' => 'Onset Type',
                            'options' => ['sudden', 'gradual', 'traumatic', 'insidious'],
                        ],
                        [
                            'name' => 'mechanism_of_injury',
                            'type' => 'textarea',
                            'label' => 'Mechanism of Injury',
                            'placeholder' => 'How did the injury occur?',
                        ],
                    ],
                ],

                // Pain Assessment
                [
                    'id' => 'pain_assessment',
                    'title' => 'Pain Assessment',
                    'fields' => [
                        [
                            'name' => 'pain_vas_current',
                            'type' => 'slider',
                            'label' => 'Current Pain (VAS 0-10)',
                            'min' => 0,
                            'max' => 10,
                            'step' => 1,
                        ],
                        [
                            'name' => 'pain_vas_worst',
                            'type' => 'slider',
                            'label' => 'Worst Pain (VAS 0-10)',
                            'min' => 0,
                            'max' => 10,
                            'step' => 1,
                        ],
                        [
                            'name' => 'pain_vas_best',
                            'type' => 'slider',
                            'label' => 'Best Pain (VAS 0-10)',
                            'min' => 0,
                            'max' => 10,
                            'step' => 1,
                        ],
                        [
                            'name' => 'pain_nature',
                            'type' => 'multiselect',
                            'label' => 'Nature of Pain',
                            'options' => [
                                'sharp', 'dull', 'aching', 'burning', 'throbbing',
                                'stabbing', 'shooting', 'tingling', 'numbness', 'cramping',
                            ],
                        ],
                        [
                            'name' => 'pain_pattern',
                            'type' => 'select',
                            'label' => 'Pain Pattern',
                            'options' => ['constant', 'intermittent', 'morning_stiffness', 'night_pain', 'activity_related'],
                        ],
                        [
                            'name' => 'aggravating_factors',
                            'type' => 'multiselect',
                            'label' => 'Aggravating Factors',
                            'options' => [
                                'walking', 'standing', 'sitting', 'lying', 'bending',
                                'lifting', 'climbing_stairs', 'cold', 'prolonged_posture',
                            ],
                        ],
                        [
                            'name' => 'relieving_factors',
                            'type' => 'multiselect',
                            'label' => 'Relieving Factors',
                            'options' => [
                                'rest', 'heat', 'cold', 'medication', 'position_change',
                                'massage', 'stretching', 'exercise',
                            ],
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
                            'name' => 'past_medical_history',
                            'type' => 'multiselect',
                            'label' => 'Past Medical History',
                            'options' => [
                                'diabetes', 'hypertension', 'cardiac_disease', 'thyroid',
                                'osteoporosis', 'rheumatoid_arthritis', 'osteoarthritis',
                                'previous_surgery', 'fracture', 'cancer', 'neurological',
                            ],
                        ],
                        [
                            'name' => 'previous_treatment',
                            'type' => 'multiselect',
                            'label' => 'Previous Treatment',
                            'options' => [
                                'physiotherapy', 'medication', 'injection', 'surgery',
                                'chiropractic', 'acupuncture', 'ayurveda', 'home_exercise',
                            ],
                        ],
                        [
                            'name' => 'previous_treatment_details',
                            'type' => 'textarea',
                            'label' => 'Previous Treatment Details',
                        ],
                        [
                            'name' => 'occupation',
                            'type' => 'text',
                            'label' => 'Occupation',
                        ],
                        [
                            'name' => 'activity_level',
                            'type' => 'select',
                            'label' => 'Activity Level',
                            'options' => ['sedentary', 'light', 'moderate', 'active', 'very_active'],
                        ],
                        [
                            'name' => 'functional_limitations',
                            'type' => 'textarea',
                            'label' => 'Functional Limitations',
                            'placeholder' => 'Activities patient cannot perform...',
                        ],
                    ],
                ],

                // Physical Examination - Observation
                [
                    'id' => 'observation',
                    'title' => 'Observation',
                    'fields' => [
                        [
                            'name' => 'posture_analysis',
                            'type' => 'select',
                            'label' => 'Posture',
                            'options' => [
                                'normal', 'kyphosis', 'lordosis', 'scoliosis',
                                'forward_head', 'rounded_shoulders', 'flat_back',
                            ],
                        ],
                        [
                            'name' => 'gait_pattern',
                            'type' => 'select',
                            'label' => 'Gait Pattern',
                            'options' => [
                                'normal', 'antalgic', 'trendelenburg', 'ataxic',
                                'steppage', 'waddling', 'scissor', 'hemiplegic',
                            ],
                        ],
                        [
                            'name' => 'swelling',
                            'type' => 'select',
                            'label' => 'Swelling',
                            'options' => ['none', 'mild', 'moderate', 'severe'],
                        ],
                        [
                            'name' => 'deformity',
                            'type' => 'text',
                            'label' => 'Deformity',
                        ],
                        [
                            'name' => 'muscle_wasting',
                            'type' => 'text',
                            'label' => 'Muscle Wasting',
                        ],
                        [
                            'name' => 'skin_changes',
                            'type' => 'multiselect',
                            'label' => 'Skin Changes',
                            'options' => ['redness', 'warmth', 'scar', 'discoloration', 'trophic_changes'],
                        ],
                    ],
                ],

                // Range of Motion
                [
                    'id' => 'rom',
                    'title' => 'Range of Motion',
                    'type' => 'rom_assessment',
                    'fields' => [
                        [
                            'name' => 'rom_region',
                            'type' => 'select',
                            'label' => 'Region',
                            'options' => ['cervical', 'thoracic', 'lumbar', 'shoulder', 'elbow', 'wrist', 'hip', 'knee', 'ankle'],
                        ],
                        [
                            'name' => 'rom_data',
                            'type' => 'rom_table',
                            'label' => 'ROM Measurements',
                            'regions' => [
                                'cervical' => [
                                    ['movement' => 'Flexion', 'normal' => '45-50°'],
                                    ['movement' => 'Extension', 'normal' => '45-50°'],
                                    ['movement' => 'Lateral Flexion R', 'normal' => '45°'],
                                    ['movement' => 'Lateral Flexion L', 'normal' => '45°'],
                                    ['movement' => 'Rotation R', 'normal' => '60-80°'],
                                    ['movement' => 'Rotation L', 'normal' => '60-80°'],
                                ],
                                'lumbar' => [
                                    ['movement' => 'Flexion', 'normal' => '40-60°'],
                                    ['movement' => 'Extension', 'normal' => '25-35°'],
                                    ['movement' => 'Lateral Flexion R', 'normal' => '25-35°'],
                                    ['movement' => 'Lateral Flexion L', 'normal' => '25-35°'],
                                ],
                                'shoulder' => [
                                    ['movement' => 'Flexion', 'normal' => '180°'],
                                    ['movement' => 'Extension', 'normal' => '60°'],
                                    ['movement' => 'Abduction', 'normal' => '180°'],
                                    ['movement' => 'Adduction', 'normal' => '45°'],
                                    ['movement' => 'Internal Rotation', 'normal' => '70°'],
                                    ['movement' => 'External Rotation', 'normal' => '90°'],
                                ],
                                'knee' => [
                                    ['movement' => 'Flexion', 'normal' => '135°'],
                                    ['movement' => 'Extension', 'normal' => '0°'],
                                ],
                                'hip' => [
                                    ['movement' => 'Flexion', 'normal' => '120°'],
                                    ['movement' => 'Extension', 'normal' => '30°'],
                                    ['movement' => 'Abduction', 'normal' => '45°'],
                                    ['movement' => 'Adduction', 'normal' => '30°'],
                                    ['movement' => 'Internal Rotation', 'normal' => '35°'],
                                    ['movement' => 'External Rotation', 'normal' => '45°'],
                                ],
                                'ankle' => [
                                    ['movement' => 'Dorsiflexion', 'normal' => '20°'],
                                    ['movement' => 'Plantarflexion', 'normal' => '50°'],
                                    ['movement' => 'Inversion', 'normal' => '35°'],
                                    ['movement' => 'Eversion', 'normal' => '20°'],
                                ],
                            ],
                        ],
                    ],
                ],

                // Muscle Strength
                [
                    'id' => 'muscle_strength',
                    'title' => 'Muscle Strength (MMT)',
                    'type' => 'mmt_assessment',
                    'fields' => [
                        [
                            'name' => 'mmt_grading',
                            'type' => 'info',
                            'label' => 'MMT Grading Scale',
                            'content' => '0=No contraction, 1=Flicker, 2=Active movement gravity eliminated, 3=Active movement against gravity, 4=Active movement against resistance, 5=Normal',
                        ],
                        [
                            'name' => 'mmt_data',
                            'type' => 'mmt_table',
                            'label' => 'Muscle Testing',
                            'columns' => ['muscle_group', 'left', 'right'],
                        ],
                    ],
                ],

                // Special Tests
                [
                    'id' => 'special_tests',
                    'title' => 'Special Tests',
                    'fields' => [
                        [
                            'name' => 'spine_tests',
                            'type' => 'multiselect_with_result',
                            'label' => 'Spine Tests',
                            'options' => [
                                ['name' => 'SLR', 'full_name' => 'Straight Leg Raise'],
                                ['name' => 'Slump Test', 'full_name' => 'Slump Test'],
                                ['name' => 'Spurling', 'full_name' => 'Spurling Test'],
                                ['name' => 'Distraction', 'full_name' => 'Cervical Distraction'],
                                ['name' => 'FABER', 'full_name' => 'FABER Test'],
                                ['name' => 'Gaenslen', 'full_name' => 'Gaenslen Test'],
                                ['name' => 'McKenzie Extension', 'full_name' => 'McKenzie Extension'],
                            ],
                            'result_options' => ['positive', 'negative', 'not_tested'],
                        ],
                        [
                            'name' => 'shoulder_tests',
                            'type' => 'multiselect_with_result',
                            'label' => 'Shoulder Tests',
                            'options' => [
                                ['name' => 'Neer', 'full_name' => 'Neer Impingement'],
                                ['name' => 'Hawkins-Kennedy', 'full_name' => 'Hawkins-Kennedy'],
                                ['name' => 'Empty Can', 'full_name' => 'Empty Can Test'],
                                ['name' => 'Speed', 'full_name' => 'Speed Test'],
                                ['name' => 'Yergason', 'full_name' => 'Yergason Test'],
                                ['name' => 'Apprehension', 'full_name' => 'Apprehension Test'],
                            ],
                            'result_options' => ['positive', 'negative', 'not_tested'],
                        ],
                        [
                            'name' => 'knee_tests',
                            'type' => 'multiselect_with_result',
                            'label' => 'Knee Tests',
                            'options' => [
                                ['name' => 'Anterior Drawer', 'full_name' => 'Anterior Drawer'],
                                ['name' => 'Lachman', 'full_name' => 'Lachman Test'],
                                ['name' => 'McMurray', 'full_name' => 'McMurray Test'],
                                ['name' => 'Valgus Stress', 'full_name' => 'Valgus Stress Test'],
                                ['name' => 'Varus Stress', 'full_name' => 'Varus Stress Test'],
                                ['name' => 'Patellar Apprehension', 'full_name' => 'Patellar Apprehension'],
                            ],
                            'result_options' => ['positive', 'negative', 'not_tested'],
                        ],
                        [
                            'name' => 'neurological_tests',
                            'type' => 'multiselect_with_result',
                            'label' => 'Neurological Tests',
                            'options' => [
                                ['name' => 'Reflexes', 'full_name' => 'Deep Tendon Reflexes'],
                                ['name' => 'Sensation', 'full_name' => 'Sensation Testing'],
                                ['name' => 'Babinski', 'full_name' => 'Babinski Sign'],
                                ['name' => 'Clonus', 'full_name' => 'Clonus'],
                            ],
                            'result_options' => ['normal', 'abnormal', 'not_tested'],
                        ],
                        [
                            'name' => 'special_test_notes',
                            'type' => 'textarea',
                            'label' => 'Test Notes',
                        ],
                    ],
                ],

                // Outcome Measures
                [
                    'id' => 'outcome_measures',
                    'title' => 'Outcome Measures',
                    'fields' => [
                        [
                            'name' => 'oswestry_score',
                            'type' => 'oswestry_calculator',
                            'label' => 'Oswestry Disability Index',
                            'condition' => ['region', 'equals', 'lumbar'],
                        ],
                        [
                            'name' => 'neck_disability_index',
                            'type' => 'ndi_calculator',
                            'label' => 'Neck Disability Index',
                            'condition' => ['region', 'equals', 'cervical'],
                        ],
                        [
                            'name' => 'spadi_score',
                            'type' => 'spadi_calculator',
                            'label' => 'SPADI Score',
                            'condition' => ['region', 'equals', 'shoulder'],
                        ],
                        [
                            'name' => 'womac_score',
                            'type' => 'womac_calculator',
                            'label' => 'WOMAC Score',
                            'condition' => ['region', 'in', ['hip', 'knee']],
                        ],
                        [
                            'name' => 'berg_balance',
                            'type' => 'berg_calculator',
                            'label' => 'Berg Balance Scale',
                        ],
                        [
                            'name' => 'timed_up_go',
                            'type' => 'number',
                            'label' => 'Timed Up and Go (seconds)',
                            'unit' => 'seconds',
                        ],
                        [
                            'name' => 'six_minute_walk',
                            'type' => 'number',
                            'label' => '6 Minute Walk Test (meters)',
                            'unit' => 'meters',
                        ],
                    ],
                ],

                // Diagnosis
                [
                    'id' => 'diagnosis',
                    'title' => 'Diagnosis',
                    'fields' => [
                        [
                            'name' => 'physiotherapy_diagnosis',
                            'type' => 'text',
                            'label' => 'Physiotherapy Diagnosis',
                            'required' => true,
                        ],
                        [
                            'name' => 'medical_diagnosis',
                            'type' => 'text',
                            'label' => 'Medical Diagnosis/Referral',
                        ],
                        [
                            'name' => 'icd_code',
                            'type' => 'text',
                            'label' => 'ICD-10 Code',
                            'placeholder' => 'e.g., M54.5',
                        ],
                        [
                            'name' => 'stage',
                            'type' => 'select',
                            'label' => 'Stage',
                            'options' => ['acute', 'subacute', 'chronic'],
                        ],
                        [
                            'name' => 'prognosis',
                            'type' => 'select',
                            'label' => 'Prognosis',
                            'options' => ['excellent', 'good', 'fair', 'poor'],
                        ],
                    ],
                ],

                // Treatment Plan
                [
                    'id' => 'treatment_plan',
                    'title' => 'Treatment Plan',
                    'fields' => [
                        [
                            'name' => 'short_term_goals',
                            'type' => 'tags',
                            'label' => 'Short Term Goals (2-4 weeks)',
                            'placeholder' => 'Add goal...',
                        ],
                        [
                            'name' => 'long_term_goals',
                            'type' => 'tags',
                            'label' => 'Long Term Goals (6-12 weeks)',
                            'placeholder' => 'Add goal...',
                        ],
                        [
                            'name' => 'treatment_frequency',
                            'type' => 'select',
                            'label' => 'Treatment Frequency',
                            'options' => [
                                ['value' => 'daily', 'label' => 'Daily'],
                                ['value' => 'alternate', 'label' => 'Alternate Days'],
                                ['value' => 'twice_week', 'label' => '2x per Week'],
                                ['value' => 'thrice_week', 'label' => '3x per Week'],
                                ['value' => 'once_week', 'label' => 'Once per Week'],
                            ],
                        ],
                        [
                            'name' => 'total_sessions',
                            'type' => 'number',
                            'label' => 'Total Sessions Planned',
                        ],
                        [
                            'name' => 'session_duration',
                            'type' => 'select',
                            'label' => 'Session Duration',
                            'options' => [
                                ['value' => 30, 'label' => '30 minutes'],
                                ['value' => 45, 'label' => '45 minutes'],
                                ['value' => 60, 'label' => '60 minutes'],
                                ['value' => 90, 'label' => '90 minutes'],
                            ],
                        ],
                    ],
                ],

                // Treatment Session
                [
                    'id' => 'treatment_session',
                    'title' => 'Treatment Session',
                    'type' => 'repeatable',
                    'fields' => [
                        [
                            'name' => 'modalities',
                            'type' => 'multiselect_with_params',
                            'label' => 'Modalities',
                            'options' => [
                                [
                                    'name' => 'TENS',
                                    'params' => ['frequency_hz', 'duration_min', 'intensity'],
                                ],
                                [
                                    'name' => 'IFT',
                                    'params' => ['frequency_hz', 'duration_min', 'carrier_frequency'],
                                ],
                                [
                                    'name' => 'Ultrasound',
                                    'params' => ['frequency_mhz', 'intensity_wcm2', 'duration_min', 'mode'],
                                ],
                                [
                                    'name' => 'SWD',
                                    'params' => ['mode', 'duration_min', 'intensity'],
                                ],
                                [
                                    'name' => 'MWD',
                                    'params' => ['duration_min', 'intensity'],
                                ],
                                [
                                    'name' => 'Laser',
                                    'params' => ['wavelength_nm', 'power_mw', 'duration_sec', 'points'],
                                ],
                                [
                                    'name' => 'Traction',
                                    'params' => ['weight_kg', 'duration_min', 'mode', 'position'],
                                ],
                                [
                                    'name' => 'Hot Pack',
                                    'params' => ['duration_min', 'area'],
                                ],
                                [
                                    'name' => 'Ice Pack',
                                    'params' => ['duration_min', 'area'],
                                ],
                                [
                                    'name' => 'Wax Bath',
                                    'params' => ['duration_min', 'dips'],
                                ],
                            ],
                        ],
                        [
                            'name' => 'manual_therapy',
                            'type' => 'multiselect',
                            'label' => 'Manual Therapy',
                            'options' => [
                                'joint_mobilization', 'joint_manipulation', 'soft_tissue_mobilization',
                                'myofascial_release', 'trigger_point_therapy', 'muscle_energy_technique',
                                'neural_mobilization', 'manual_traction', 'proprioceptive_neuromuscular_facilitation',
                            ],
                        ],
                        [
                            'name' => 'exercises',
                            'type' => 'exercise_prescription',
                            'label' => 'Exercises Performed',
                            'categories' => [
                                'stretching', 'strengthening', 'rom_exercises', 'balance',
                                'core_stabilization', 'aerobic', 'functional', 'gait_training',
                            ],
                        ],
                        [
                            'name' => 'session_notes',
                            'type' => 'textarea',
                            'label' => 'Session Notes',
                        ],
                        [
                            'name' => 'pain_after_treatment',
                            'type' => 'slider',
                            'label' => 'Pain After Treatment (VAS)',
                            'min' => 0,
                            'max' => 10,
                        ],
                        [
                            'name' => 'patient_response',
                            'type' => 'select',
                            'label' => 'Patient Response',
                            'options' => ['improved', 'same', 'worse', 'not_assessed'],
                        ],
                    ],
                ],

                // Home Exercise Program (HEP)
                [
                    'id' => 'hep',
                    'title' => 'Home Exercise Program',
                    'type' => 'hep_builder',
                    'fields' => [
                        [
                            'name' => 'hep_exercises',
                            'type' => 'exercise_list',
                            'label' => 'Exercises',
                            'exercise_library' => true,
                            'fields_per_exercise' => ['sets', 'reps', 'hold_seconds', 'frequency', 'instructions'],
                        ],
                        [
                            'name' => 'hep_precautions',
                            'type' => 'textarea',
                            'label' => 'Precautions/Warnings',
                        ],
                        [
                            'name' => 'hep_progression',
                            'type' => 'textarea',
                            'label' => 'Progression Guidelines',
                        ],
                        [
                            'name' => 'generate_pdf',
                            'type' => 'button',
                            'label' => 'Generate HEP PDF',
                            'action' => 'generate_hep_pdf',
                        ],
                        [
                            'name' => 'send_whatsapp',
                            'type' => 'button',
                            'label' => 'Send via WhatsApp',
                            'action' => 'send_hep_whatsapp',
                        ],
                    ],
                ],

                // Plan/Follow-up
                [
                    'id' => 'plan',
                    'title' => 'Plan',
                    'fields' => [
                        [
                            'name' => 'advice',
                            'type' => 'textarea',
                            'label' => 'Advice/Instructions',
                        ],
                        [
                            'name' => 'activity_modifications',
                            'type' => 'multiselect',
                            'label' => 'Activity Modifications',
                            'options' => [
                                'avoid_lifting', 'avoid_prolonged_sitting', 'avoid_bending',
                                'use_lumbar_support', 'ice_application', 'heat_application',
                                'bed_rest', 'gradual_return_activity', 'ergonomic_advice',
                            ],
                        ],
                        [
                            'name' => 'equipment_advised',
                            'type' => 'multiselect',
                            'label' => 'Equipment/Aids',
                            'options' => [
                                'cervical_collar', 'lumbar_belt', 'knee_brace', 'ankle_brace',
                                'wrist_splint', 'walking_stick', 'walker', 'wheelchair',
                                'tens_unit', 'exercise_band', 'foam_roller',
                            ],
                        ],
                        [
                            'name' => 'next_session',
                            'type' => 'datetime',
                            'label' => 'Next Session',
                        ],
                        [
                            'name' => 'discharge_criteria',
                            'type' => 'textarea',
                            'label' => 'Discharge Criteria',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Calculate Oswestry Disability Index
     */
    public static function calculateOswestry(array $responses): array
    {
        Log::info('Calculating Oswestry Disability Index', ['responses' => $responses]);

        $totalScore = array_sum($responses);
        $maxPossible = count($responses) * 5;
        $percentage = ($totalScore / $maxPossible) * 100;

        $interpretation = match (true) {
            $percentage <= 20 => 'Minimal Disability',
            $percentage <= 40 => 'Moderate Disability',
            $percentage <= 60 => 'Severe Disability',
            $percentage <= 80 => 'Crippled',
            default => 'Bed-bound or exaggerating',
        };

        Log::info('Oswestry calculated', [
            'score' => $totalScore,
            'percentage' => $percentage,
            'interpretation' => $interpretation,
        ]);

        return [
            'score' => $totalScore,
            'percentage' => round($percentage, 1),
            'interpretation' => $interpretation,
        ];
    }

    /**
     * Calculate Neck Disability Index
     */
    public static function calculateNDI(array $responses): array
    {
        Log::info('Calculating Neck Disability Index', ['responses' => $responses]);

        $totalScore = array_sum($responses);
        $maxPossible = 50;
        $percentage = ($totalScore / $maxPossible) * 100;

        $interpretation = match (true) {
            $percentage <= 8 => 'No Disability',
            $percentage <= 28 => 'Mild Disability',
            $percentage <= 48 => 'Moderate Disability',
            $percentage <= 68 => 'Severe Disability',
            default => 'Complete Disability',
        };

        Log::info('NDI calculated', [
            'score' => $totalScore,
            'percentage' => $percentage,
            'interpretation' => $interpretation,
        ]);

        return [
            'score' => $totalScore,
            'percentage' => round($percentage, 1),
            'interpretation' => $interpretation,
        ];
    }

    /**
     * Get common physiotherapy diagnoses
     */
    public static function getCommonDiagnoses(): array
    {
        return [
            // Spine
            ['code' => 'M54.5', 'name' => 'Low back pain'],
            ['code' => 'M54.2', 'name' => 'Cervicalgia'],
            ['code' => 'M51.1', 'name' => 'Lumbar disc herniation'],
            ['code' => 'M50.1', 'name' => 'Cervical disc herniation'],
            ['code' => 'M47.816', 'name' => 'Cervical spondylosis'],
            ['code' => 'M47.817', 'name' => 'Lumbar spondylosis'],
            ['code' => 'M48.06', 'name' => 'Spinal stenosis, lumbar'],
            
            // Shoulder
            ['code' => 'M75.1', 'name' => 'Rotator cuff syndrome'],
            ['code' => 'M75.0', 'name' => 'Adhesive capsulitis (Frozen shoulder)'],
            ['code' => 'M75.4', 'name' => 'Impingement syndrome'],
            ['code' => 'S43.4', 'name' => 'Shoulder sprain'],
            
            // Knee
            ['code' => 'M17.1', 'name' => 'Primary osteoarthritis, knee'],
            ['code' => 'S83.5', 'name' => 'ACL injury'],
            ['code' => 'S83.2', 'name' => 'Meniscal tear'],
            ['code' => 'M22.0', 'name' => 'Patellofemoral syndrome'],
            ['code' => 'M76.5', 'name' => 'Patellar tendinitis'],
            
            // Hip
            ['code' => 'M16.1', 'name' => 'Primary osteoarthritis, hip'],
            ['code' => 'M70.6', 'name' => 'Trochanteric bursitis'],
            
            // Ankle/Foot
            ['code' => 'S93.4', 'name' => 'Ankle sprain'],
            ['code' => 'M72.2', 'name' => 'Plantar fasciitis'],
            ['code' => 'M76.6', 'name' => 'Achilles tendinitis'],
            
            // Elbow/Wrist
            ['code' => 'M77.1', 'name' => 'Lateral epicondylitis'],
            ['code' => 'M77.0', 'name' => 'Medial epicondylitis'],
            ['code' => 'G56.0', 'name' => 'Carpal tunnel syndrome'],
            
            // Other
            ['code' => 'G81.9', 'name' => 'Hemiplegia'],
            ['code' => 'G82.2', 'name' => 'Paraplegia'],
            ['code' => 'G35', 'name' => 'Multiple sclerosis'],
            ['code' => 'G20', 'name' => 'Parkinson\'s disease'],
        ];
    }

    /**
     * Get exercise library for HEP
     */
    public static function getExerciseLibrary(): array
    {
        return [
            'cervical' => [
                ['name' => 'Chin Tucks', 'category' => 'strengthening', 'video_url' => null],
                ['name' => 'Cervical Rotation', 'category' => 'rom', 'video_url' => null],
                ['name' => 'Cervical Side Flexion', 'category' => 'stretching', 'video_url' => null],
                ['name' => 'Levator Scapulae Stretch', 'category' => 'stretching', 'video_url' => null],
                ['name' => 'Upper Trapezius Stretch', 'category' => 'stretching', 'video_url' => null],
                ['name' => 'Isometric Neck Strengthening', 'category' => 'strengthening', 'video_url' => null],
            ],
            'lumbar' => [
                ['name' => 'Cat-Camel', 'category' => 'mobility', 'video_url' => null],
                ['name' => 'Bird-Dog', 'category' => 'core', 'video_url' => null],
                ['name' => 'Pelvic Tilts', 'category' => 'core', 'video_url' => null],
                ['name' => 'Knee to Chest', 'category' => 'stretching', 'video_url' => null],
                ['name' => 'McKenzie Extension', 'category' => 'mobility', 'video_url' => null],
                ['name' => 'Piriformis Stretch', 'category' => 'stretching', 'video_url' => null],
                ['name' => 'Dead Bug', 'category' => 'core', 'video_url' => null],
                ['name' => 'Bridge', 'category' => 'strengthening', 'video_url' => null],
            ],
            'shoulder' => [
                ['name' => 'Pendulum Exercises', 'category' => 'mobility', 'video_url' => null],
                ['name' => 'Wall Slides', 'category' => 'mobility', 'video_url' => null],
                ['name' => 'External Rotation with Band', 'category' => 'strengthening', 'video_url' => null],
                ['name' => 'Internal Rotation with Band', 'category' => 'strengthening', 'video_url' => null],
                ['name' => 'Sleeper Stretch', 'category' => 'stretching', 'video_url' => null],
                ['name' => 'Cross Body Stretch', 'category' => 'stretching', 'video_url' => null],
                ['name' => 'Scapular Squeezes', 'category' => 'strengthening', 'video_url' => null],
            ],
            'knee' => [
                ['name' => 'Quad Sets', 'category' => 'strengthening', 'video_url' => null],
                ['name' => 'Straight Leg Raises', 'category' => 'strengthening', 'video_url' => null],
                ['name' => 'Heel Slides', 'category' => 'rom', 'video_url' => null],
                ['name' => 'Mini Squats', 'category' => 'strengthening', 'video_url' => null],
                ['name' => 'Step Ups', 'category' => 'strengthening', 'video_url' => null],
                ['name' => 'Hamstring Curl', 'category' => 'strengthening', 'video_url' => null],
                ['name' => 'Calf Raises', 'category' => 'strengthening', 'video_url' => null],
            ],
            'ankle' => [
                ['name' => 'Ankle Pumps', 'category' => 'mobility', 'video_url' => null],
                ['name' => 'Ankle Circles', 'category' => 'mobility', 'video_url' => null],
                ['name' => 'Calf Stretch (Wall)', 'category' => 'stretching', 'video_url' => null],
                ['name' => 'Toe Raises', 'category' => 'strengthening', 'video_url' => null],
                ['name' => 'Single Leg Balance', 'category' => 'balance', 'video_url' => null],
                ['name' => 'Theraband Resistance', 'category' => 'strengthening', 'video_url' => null],
            ],
            'balance' => [
                ['name' => 'Single Leg Stance', 'category' => 'balance', 'video_url' => null],
                ['name' => 'Tandem Standing', 'category' => 'balance', 'video_url' => null],
                ['name' => 'Heel-Toe Walking', 'category' => 'balance', 'video_url' => null],
                ['name' => 'Foam Pad Balance', 'category' => 'balance', 'video_url' => null],
            ],
        ];
    }
}
