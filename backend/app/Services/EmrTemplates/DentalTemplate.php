<?php

namespace App\Services\EmrTemplates;

use Illuminate\Support\Facades\Log;

class DentalTemplate
{
    /**
     * Get dental EMR template fields
     */
    public static function getFields(): array
    {
        Log::info('Loading Dental EMR template');

        return [
            'specialty' => 'dental',
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
                            'placeholder' => 'Main dental complaint...',
                        ],
                        [
                            'name' => 'pain_present',
                            'type' => 'boolean',
                            'label' => 'Pain Present',
                        ],
                        [
                            'name' => 'pain_tooth_number',
                            'type' => 'tooth_selector',
                            'label' => 'Pain Location (Tooth #)',
                            'condition' => ['pain_present', 'equals', true],
                        ],
                        [
                            'name' => 'pain_severity',
                            'type' => 'slider',
                            'label' => 'Pain Severity (0-10)',
                            'min' => 0,
                            'max' => 10,
                            'condition' => ['pain_present', 'equals', true],
                        ],
                        [
                            'name' => 'pain_type',
                            'type' => 'multiselect',
                            'label' => 'Pain Type',
                            'options' => [
                                'sharp', 'dull', 'throbbing', 'constant', 'intermittent',
                                'spontaneous', 'provoked', 'radiating', 'localized',
                            ],
                            'condition' => ['pain_present', 'equals', true],
                        ],
                        [
                            'name' => 'pain_triggers',
                            'type' => 'multiselect',
                            'label' => 'Pain Triggers',
                            'options' => [
                                'hot', 'cold', 'sweet', 'chewing', 'touch',
                                'lying_down', 'spontaneous',
                            ],
                            'condition' => ['pain_present', 'equals', true],
                        ],
                        [
                            'name' => 'duration',
                            'type' => 'text',
                            'label' => 'Duration',
                            'placeholder' => 'e.g., 2 days, 1 week',
                        ],
                    ],
                ],

                // Medical History
                [
                    'id' => 'medical_history',
                    'title' => 'Medical History',
                    'fields' => [
                        [
                            'name' => 'medical_conditions',
                            'type' => 'multiselect',
                            'label' => 'Medical Conditions',
                            'options' => [
                                'diabetes', 'hypertension', 'heart_disease', 'bleeding_disorder',
                                'hepatitis', 'hiv', 'asthma', 'epilepsy', 'thyroid',
                                'kidney_disease', 'liver_disease', 'rheumatic_fever',
                                'joint_replacement', 'chemotherapy', 'radiotherapy',
                            ],
                        ],
                        [
                            'name' => 'allergies',
                            'type' => 'tags',
                            'label' => 'Drug Allergies',
                            'suggestions' => ['penicillin', 'sulfa', 'aspirin', 'latex', 'local_anesthesia'],
                        ],
                        [
                            'name' => 'current_medications',
                            'type' => 'tags',
                            'label' => 'Current Medications',
                        ],
                        [
                            'name' => 'blood_thinners',
                            'type' => 'boolean',
                            'label' => 'On Blood Thinners (Aspirin/Warfarin/Clopidogrel)',
                        ],
                        [
                            'name' => 'bisphosphonates',
                            'type' => 'boolean',
                            'label' => 'On Bisphosphonates',
                        ],
                        [
                            'name' => 'pregnancy',
                            'type' => 'select',
                            'label' => 'Pregnancy Status',
                            'options' => ['not_applicable', 'not_pregnant', 'pregnant_1st_trimester', 'pregnant_2nd_trimester', 'pregnant_3rd_trimester', 'breastfeeding'],
                        ],
                        [
                            'name' => 'last_dental_visit',
                            'type' => 'text',
                            'label' => 'Last Dental Visit',
                            'placeholder' => 'e.g., 6 months ago, 2 years ago',
                        ],
                    ],
                ],

                // Extra-Oral Examination
                [
                    'id' => 'extra_oral',
                    'title' => 'Extra-Oral Examination',
                    'fields' => [
                        [
                            'name' => 'facial_symmetry',
                            'type' => 'select',
                            'label' => 'Facial Symmetry',
                            'options' => ['symmetrical', 'asymmetrical'],
                        ],
                        [
                            'name' => 'facial_swelling',
                            'type' => 'multiselect',
                            'label' => 'Facial Swelling',
                            'options' => ['none', 'right_side', 'left_side', 'diffuse', 'localized'],
                        ],
                        [
                            'name' => 'lymph_nodes',
                            'type' => 'select',
                            'label' => 'Lymph Nodes',
                            'options' => ['not_palpable', 'palpable_non_tender', 'palpable_tender', 'enlarged'],
                        ],
                        [
                            'name' => 'tmj_examination',
                            'type' => 'multiselect',
                            'label' => 'TMJ Examination',
                            'options' => ['normal', 'clicking', 'crepitus', 'deviation', 'limited_opening', 'pain'],
                        ],
                        [
                            'name' => 'mouth_opening',
                            'type' => 'number',
                            'label' => 'Mouth Opening (mm)',
                            'unit' => 'mm',
                            'normal_range' => '35-55',
                        ],
                        [
                            'name' => 'lips',
                            'type' => 'select',
                            'label' => 'Lips',
                            'options' => ['normal', 'cheilitis', 'angular_cheilitis', 'herpes_labialis', 'dry', 'pigmented'],
                        ],
                    ],
                ],

                // Intra-Oral Examination
                [
                    'id' => 'intra_oral',
                    'title' => 'Intra-Oral Examination',
                    'fields' => [
                        [
                            'name' => 'soft_tissue',
                            'type' => 'multiselect',
                            'label' => 'Soft Tissue',
                            'options' => [
                                'normal', 'ulcer', 'white_patch', 'red_patch', 'swelling',
                                'pigmentation', 'vesicles', 'erosion',
                            ],
                        ],
                        [
                            'name' => 'soft_tissue_location',
                            'type' => 'multiselect',
                            'label' => 'Location of Lesion',
                            'options' => [
                                'buccal_mucosa', 'tongue', 'floor_of_mouth', 'palate',
                                'gingiva', 'lip', 'retromolar',
                            ],
                            'condition' => ['soft_tissue', 'not_equals', ['normal']],
                        ],
                        [
                            'name' => 'tongue',
                            'type' => 'multiselect',
                            'label' => 'Tongue',
                            'options' => ['normal', 'coated', 'fissured', 'geographic', 'atrophic', 'macroglossia', 'deviation'],
                        ],
                        [
                            'name' => 'gingiva',
                            'type' => 'multiselect',
                            'label' => 'Gingiva',
                            'options' => ['healthy', 'generalized_gingivitis', 'localized_gingivitis', 'periodontitis', 'recession', 'hyperplasia'],
                        ],
                        [
                            'name' => 'oral_hygiene',
                            'type' => 'select',
                            'label' => 'Oral Hygiene',
                            'options' => ['excellent', 'good', 'fair', 'poor'],
                        ],
                        [
                            'name' => 'calculus',
                            'type' => 'select',
                            'label' => 'Calculus',
                            'options' => ['none', 'mild', 'moderate', 'heavy'],
                        ],
                        [
                            'name' => 'staining',
                            'type' => 'multiselect',
                            'label' => 'Staining',
                            'options' => ['none', 'tobacco', 'betel', 'tea_coffee', 'fluorosis', 'tetracycline'],
                        ],
                    ],
                ],

                // Dental Chart (Odontogram)
                [
                    'id' => 'dental_chart',
                    'title' => 'Dental Chart',
                    'type' => 'odontogram',
                    'fields' => [
                        [
                            'name' => 'tooth_chart',
                            'type' => 'odontogram',
                            'label' => 'Tooth Chart',
                            'notation' => 'fdi', // FDI World Dental Federation notation
                            'chart_type' => 'interactive',
                            'tooth_statuses' => [
                                'present', 'missing', 'caries', 'filled', 'crown',
                                'bridge', 'implant', 'rct', 'root_stump', 'impacted',
                                'unerupted', 'mobility', 'fracture',
                            ],
                            'surfaces' => ['mesial', 'distal', 'occlusal', 'buccal', 'lingual'],
                        ],
                        [
                            'name' => 'teeth_present',
                            'type' => 'number',
                            'label' => 'Teeth Present',
                            'calculated' => true,
                        ],
                        [
                            'name' => 'teeth_missing',
                            'type' => 'number',
                            'label' => 'Teeth Missing',
                            'calculated' => true,
                        ],
                        [
                            'name' => 'dmft_index',
                            'type' => 'dmft_calculator',
                            'label' => 'DMFT Index',
                            'calculated' => true,
                        ],
                    ],
                ],

                // Periodontal Assessment
                [
                    'id' => 'periodontal',
                    'title' => 'Periodontal Assessment',
                    'fields' => [
                        [
                            'name' => 'periodontal_charting',
                            'type' => 'perio_chart',
                            'label' => 'Periodontal Charting',
                            'measurements' => ['probing_depth', 'recession', 'cal', 'bleeding_on_probing', 'mobility', 'furcation'],
                        ],
                        [
                            'name' => 'bpe_score',
                            'type' => 'bpe_chart',
                            'label' => 'BPE/CPITN Score',
                            'sextants' => 6,
                        ],
                        [
                            'name' => 'bleeding_on_probing',
                            'type' => 'percentage',
                            'label' => 'Bleeding on Probing %',
                        ],
                        [
                            'name' => 'periodontal_diagnosis',
                            'type' => 'select',
                            'label' => 'Periodontal Diagnosis',
                            'options' => [
                                'healthy', 'gingivitis', 'stage_1_periodontitis',
                                'stage_2_periodontitis', 'stage_3_periodontitis', 'stage_4_periodontitis',
                            ],
                        ],
                    ],
                ],

                // Radiographic Findings
                [
                    'id' => 'radiographs',
                    'title' => 'Radiographic Findings',
                    'fields' => [
                        [
                            'name' => 'xrays_taken',
                            'type' => 'multiselect',
                            'label' => 'X-rays Taken',
                            'options' => [
                                'iopa', 'bitewing', 'opg', 'lateral_cephalogram',
                                'occlusal', 'cbct',
                            ],
                        ],
                        [
                            'name' => 'radiographic_findings',
                            'type' => 'textarea',
                            'label' => 'Radiographic Findings',
                            'placeholder' => 'Describe findings...',
                        ],
                        [
                            'name' => 'bone_loss',
                            'type' => 'multiselect',
                            'label' => 'Bone Loss Pattern',
                            'options' => ['none', 'horizontal', 'vertical', 'localized', 'generalized'],
                        ],
                        [
                            'name' => 'periapical_pathology',
                            'type' => 'boolean',
                            'label' => 'Periapical Pathology Present',
                        ],
                        [
                            'name' => 'pathology_tooth',
                            'type' => 'tooth_selector',
                            'label' => 'Tooth with Pathology',
                            'condition' => ['periapical_pathology', 'equals', true],
                        ],
                        [
                            'name' => 'xray_images',
                            'type' => 'image_upload',
                            'label' => 'X-ray Images',
                            'accept' => ['image/*'],
                        ],
                    ],
                ],

                // Diagnosis
                [
                    'id' => 'diagnosis',
                    'title' => 'Diagnosis',
                    'fields' => [
                        [
                            'name' => 'primary_diagnosis',
                            'type' => 'text',
                            'label' => 'Primary Diagnosis',
                            'required' => true,
                            'autocomplete' => 'icd10_dental',
                        ],
                        [
                            'name' => 'secondary_diagnosis',
                            'type' => 'tags',
                            'label' => 'Secondary Diagnosis',
                        ],
                        [
                            'name' => 'icd_code',
                            'type' => 'text',
                            'label' => 'ICD-10 Code',
                            'placeholder' => 'e.g., K02.1',
                        ],
                        [
                            'name' => 'prognosis',
                            'type' => 'select',
                            'label' => 'Prognosis',
                            'options' => ['excellent', 'good', 'fair', 'poor', 'hopeless'],
                        ],
                    ],
                ],

                // Treatment Plan
                [
                    'id' => 'treatment_plan',
                    'title' => 'Treatment Plan',
                    'type' => 'treatment_planner',
                    'fields' => [
                        [
                            'name' => 'phase_1_emergency',
                            'type' => 'treatment_phase',
                            'label' => 'Phase 1: Emergency/Immediate',
                            'procedures' => ['extraction', 'incision_drainage', 'pulpotomy', 'emergency_rct'],
                        ],
                        [
                            'name' => 'phase_2_disease_control',
                            'type' => 'treatment_phase',
                            'label' => 'Phase 2: Disease Control',
                            'procedures' => ['scaling', 'root_planing', 'restorations', 'rct'],
                        ],
                        [
                            'name' => 'phase_3_surgical',
                            'type' => 'treatment_phase',
                            'label' => 'Phase 3: Surgical',
                            'procedures' => ['extraction', 'periodontal_surgery', 'implant', 'apicoectomy'],
                        ],
                        [
                            'name' => 'phase_4_prosthetic',
                            'type' => 'treatment_phase',
                            'label' => 'Phase 4: Prosthetic',
                            'procedures' => ['crown', 'bridge', 'denture', 'implant_prosthesis'],
                        ],
                        [
                            'name' => 'phase_5_maintenance',
                            'type' => 'treatment_phase',
                            'label' => 'Phase 5: Maintenance',
                            'procedures' => ['recall', 'prophylaxis', 'fluoride'],
                        ],
                    ],
                ],

                // Procedure Performed
                [
                    'id' => 'procedure',
                    'title' => 'Procedure Performed',
                    'type' => 'repeatable',
                    'fields' => [
                        [
                            'name' => 'procedure_type',
                            'type' => 'select',
                            'label' => 'Procedure',
                            'options' => [
                                // Preventive
                                ['group' => 'Preventive', 'value' => 'scaling', 'label' => 'Scaling & Polishing'],
                                ['group' => 'Preventive', 'value' => 'fluoride', 'label' => 'Fluoride Application'],
                                ['group' => 'Preventive', 'value' => 'sealant', 'label' => 'Pit & Fissure Sealant'],
                                
                                // Restorative
                                ['group' => 'Restorative', 'value' => 'filling_composite', 'label' => 'Composite Filling'],
                                ['group' => 'Restorative', 'value' => 'filling_gic', 'label' => 'GIC Filling'],
                                ['group' => 'Restorative', 'value' => 'filling_amalgam', 'label' => 'Amalgam Filling'],
                                ['group' => 'Restorative', 'value' => 'inlay_onlay', 'label' => 'Inlay/Onlay'],
                                
                                // Endodontic
                                ['group' => 'Endodontic', 'value' => 'rct_single', 'label' => 'RCT - Single Canal'],
                                ['group' => 'Endodontic', 'value' => 'rct_multi', 'label' => 'RCT - Multi Canal'],
                                ['group' => 'Endodontic', 'value' => 'pulpotomy', 'label' => 'Pulpotomy'],
                                ['group' => 'Endodontic', 'value' => 'retreatment', 'label' => 'RCT Retreatment'],
                                
                                // Prosthodontic
                                ['group' => 'Prosthodontic', 'value' => 'crown_pfc', 'label' => 'Crown - PFM'],
                                ['group' => 'Prosthodontic', 'value' => 'crown_ceramic', 'label' => 'Crown - All Ceramic'],
                                ['group' => 'Prosthodontic', 'value' => 'crown_zirconia', 'label' => 'Crown - Zirconia'],
                                ['group' => 'Prosthodontic', 'value' => 'bridge', 'label' => 'Bridge'],
                                ['group' => 'Prosthodontic', 'value' => 'rpd', 'label' => 'Removable Partial Denture'],
                                ['group' => 'Prosthodontic', 'value' => 'cd', 'label' => 'Complete Denture'],
                                ['group' => 'Prosthodontic', 'value' => 'veneer', 'label' => 'Veneer'],
                                
                                // Surgical
                                ['group' => 'Surgical', 'value' => 'extraction_simple', 'label' => 'Simple Extraction'],
                                ['group' => 'Surgical', 'value' => 'extraction_surgical', 'label' => 'Surgical Extraction'],
                                ['group' => 'Surgical', 'value' => 'extraction_wisdom', 'label' => 'Wisdom Tooth Extraction'],
                                ['group' => 'Surgical', 'value' => 'implant', 'label' => 'Dental Implant'],
                                ['group' => 'Surgical', 'value' => 'apicoectomy', 'label' => 'Apicoectomy'],
                                
                                // Periodontal
                                ['group' => 'Periodontal', 'value' => 'deep_scaling', 'label' => 'Deep Scaling/Root Planing'],
                                ['group' => 'Periodontal', 'value' => 'flap_surgery', 'label' => 'Flap Surgery'],
                                ['group' => 'Periodontal', 'value' => 'gingivectomy', 'label' => 'Gingivectomy'],
                                ['group' => 'Periodontal', 'value' => 'bone_graft', 'label' => 'Bone Graft'],
                                
                                // Orthodontic
                                ['group' => 'Orthodontic', 'value' => 'braces_metal', 'label' => 'Metal Braces'],
                                ['group' => 'Orthodontic', 'value' => 'braces_ceramic', 'label' => 'Ceramic Braces'],
                                ['group' => 'Orthodontic', 'value' => 'aligners', 'label' => 'Clear Aligners'],
                                ['group' => 'Orthodontic', 'value' => 'retainer', 'label' => 'Retainer'],
                            ],
                        ],
                        [
                            'name' => 'tooth_number',
                            'type' => 'tooth_selector',
                            'label' => 'Tooth Number',
                            'multiple' => true,
                        ],
                        [
                            'name' => 'surfaces_treated',
                            'type' => 'multiselect',
                            'label' => 'Surfaces Treated',
                            'options' => ['mesial', 'distal', 'occlusal', 'buccal', 'lingual', 'incisal'],
                        ],
                        [
                            'name' => 'anesthesia',
                            'type' => 'select',
                            'label' => 'Anesthesia',
                            'options' => [
                                'none', 'topical', 'infiltration', 'nerve_block',
                                'intraligamentary', 'general',
                            ],
                        ],
                        [
                            'name' => 'anesthesia_details',
                            'type' => 'text',
                            'label' => 'Anesthesia Details',
                            'placeholder' => 'e.g., 2% Lidocaine 1.8ml',
                        ],
                        [
                            'name' => 'materials_used',
                            'type' => 'tags',
                            'label' => 'Materials Used',
                        ],
                        [
                            'name' => 'procedure_notes',
                            'type' => 'textarea',
                            'label' => 'Procedure Notes',
                        ],
                        [
                            'name' => 'complications',
                            'type' => 'textarea',
                            'label' => 'Complications (if any)',
                        ],
                    ],
                ],

                // Lab Work
                [
                    'id' => 'lab_work',
                    'title' => 'Lab Work',
                    'type' => 'lab_order',
                    'fields' => [
                        [
                            'name' => 'lab_order_type',
                            'type' => 'select',
                            'label' => 'Work Type',
                            'options' => [
                                'crown', 'bridge', 'denture', 'retainer',
                                'nightguard', 'splint', 'study_model', 'surgical_guide',
                            ],
                        ],
                        [
                            'name' => 'shade',
                            'type' => 'select',
                            'label' => 'Shade',
                            'options' => [
                                'A1', 'A2', 'A3', 'A3.5', 'A4',
                                'B1', 'B2', 'B3', 'B4',
                                'C1', 'C2', 'C3', 'C4',
                                'D2', 'D3', 'D4',
                            ],
                        ],
                        [
                            'name' => 'material',
                            'type' => 'select',
                            'label' => 'Material',
                            'options' => [
                                'pfm', 'zirconia', 'emax', 'ceramic', 'acrylic',
                                'metal', 'flexible', 'cast_partial',
                            ],
                        ],
                        [
                            'name' => 'lab_name',
                            'type' => 'text',
                            'label' => 'Lab Name',
                        ],
                        [
                            'name' => 'impression_sent_date',
                            'type' => 'date',
                            'label' => 'Impression/Scan Sent',
                        ],
                        [
                            'name' => 'expected_delivery',
                            'type' => 'date',
                            'label' => 'Expected Delivery',
                        ],
                        [
                            'name' => 'lab_notes',
                            'type' => 'textarea',
                            'label' => 'Lab Instructions',
                        ],
                    ],
                ],

                // Prescription
                [
                    'id' => 'prescription',
                    'title' => 'Prescription',
                    'type' => 'prescription',
                    'common_drugs' => [
                        // Antibiotics
                        ['name' => 'Amoxicillin 500mg', 'category' => 'Antibiotic', 'form' => 'capsule'],
                        ['name' => 'Amoxicillin + Clavulanic Acid 625mg', 'category' => 'Antibiotic', 'form' => 'tablet'],
                        ['name' => 'Azithromycin 500mg', 'category' => 'Antibiotic', 'form' => 'tablet'],
                        ['name' => 'Metronidazole 400mg', 'category' => 'Antibiotic', 'form' => 'tablet'],
                        ['name' => 'Clindamycin 300mg', 'category' => 'Antibiotic', 'form' => 'capsule'],
                        
                        // Analgesics
                        ['name' => 'Ibuprofen 400mg', 'category' => 'NSAID', 'form' => 'tablet'],
                        ['name' => 'Diclofenac 50mg', 'category' => 'NSAID', 'form' => 'tablet'],
                        ['name' => 'Paracetamol 500mg', 'category' => 'Analgesic', 'form' => 'tablet'],
                        ['name' => 'Aceclofenac 100mg + Paracetamol 325mg', 'category' => 'NSAID', 'form' => 'tablet'],
                        ['name' => 'Ketorolac 10mg', 'category' => 'NSAID', 'form' => 'tablet'],
                        
                        // Mouthwash
                        ['name' => 'Chlorhexidine 0.2% Mouthwash', 'category' => 'Antiseptic', 'form' => 'liquid'],
                        ['name' => 'Povidone Iodine Mouthwash', 'category' => 'Antiseptic', 'form' => 'liquid'],
                        ['name' => 'Benzydamine Mouthwash', 'category' => 'Anti-inflammatory', 'form' => 'liquid'],
                        
                        // Topical
                        ['name' => 'Lignocaine 2% Gel', 'category' => 'Anesthetic', 'form' => 'gel'],
                        ['name' => 'Triamcinolone Acetonide 0.1% Paste', 'category' => 'Steroid', 'form' => 'paste'],
                        
                        // Others
                        ['name' => 'Ranitidine 150mg', 'category' => 'Antacid', 'form' => 'tablet'],
                        ['name' => 'Pantoprazole 40mg', 'category' => 'PPI', 'form' => 'tablet'],
                    ],
                ],

                // Post-Op Instructions
                [
                    'id' => 'post_op',
                    'title' => 'Post-Op Instructions',
                    'fields' => [
                        [
                            'name' => 'post_op_instructions',
                            'type' => 'checklist',
                            'label' => 'Instructions Given',
                            'options' => [
                                'bite_on_gauze' => 'Bite on gauze for 30-45 mins',
                                'no_spitting' => 'Do not spit or rinse for 24 hours',
                                'cold_compress' => 'Apply cold compress',
                                'soft_diet' => 'Soft diet for 24-48 hours',
                                'no_hot_food' => 'Avoid hot food/drinks',
                                'no_smoking' => 'No smoking for 48-72 hours',
                                'no_straw' => 'Do not use straw',
                                'saltwater_rinse' => 'Warm salt water rinses after 24 hours',
                                'pain_medication' => 'Take pain medication as prescribed',
                                'antibiotics' => 'Complete antibiotic course',
                            ],
                        ],
                        [
                            'name' => 'custom_instructions',
                            'type' => 'textarea',
                            'label' => 'Additional Instructions',
                        ],
                        [
                            'name' => 'warning_signs',
                            'type' => 'textarea',
                            'label' => 'Warning Signs to Report',
                            'default' => 'Contact clinic if: severe bleeding, increasing pain after 2-3 days, fever, difficulty swallowing, pus discharge',
                        ],
                    ],
                ],

                // Follow-up
                [
                    'id' => 'followup',
                    'title' => 'Follow-up',
                    'fields' => [
                        [
                            'name' => 'next_appointment',
                            'type' => 'datetime',
                            'label' => 'Next Appointment',
                        ],
                        [
                            'name' => 'next_procedure',
                            'type' => 'text',
                            'label' => 'Next Procedure',
                        ],
                        [
                            'name' => 'recall_interval',
                            'type' => 'select',
                            'label' => 'Recall Interval',
                            'options' => [
                                ['value' => 3, 'label' => '3 Months'],
                                ['value' => 6, 'label' => '6 Months'],
                                ['value' => 12, 'label' => '12 Months'],
                            ],
                        ],
                        [
                            'name' => 'treatment_notes',
                            'type' => 'textarea',
                            'label' => 'Visit Notes',
                        ],
                    ],
                ],

                // Clinical Photos
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
                            'tags' => ['before', 'after', 'xray', 'intraoral', 'extraoral'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get FDI tooth notation
     */
    public static function getToothNotation(): array
    {
        Log::info('Loading FDI tooth notation');

        return [
            'permanent' => [
                'upper_right' => [18, 17, 16, 15, 14, 13, 12, 11],
                'upper_left' => [21, 22, 23, 24, 25, 26, 27, 28],
                'lower_left' => [31, 32, 33, 34, 35, 36, 37, 38],
                'lower_right' => [48, 47, 46, 45, 44, 43, 42, 41],
            ],
            'deciduous' => [
                'upper_right' => [55, 54, 53, 52, 51],
                'upper_left' => [61, 62, 63, 64, 65],
                'lower_left' => [71, 72, 73, 74, 75],
                'lower_right' => [85, 84, 83, 82, 81],
            ],
            'tooth_names' => [
                1 => 'Central Incisor',
                2 => 'Lateral Incisor',
                3 => 'Canine',
                4 => 'First Premolar',
                5 => 'Second Premolar',
                6 => 'First Molar',
                7 => 'Second Molar',
                8 => 'Third Molar (Wisdom)',
            ],
        ];
    }

    /**
     * Calculate DMFT Index
     */
    public static function calculateDMFT(array $toothData): array
    {
        Log::info('Calculating DMFT Index', ['toothData' => $toothData]);

        $decayed = 0;
        $missing = 0;
        $filled = 0;

        foreach ($toothData as $tooth => $status) {
            switch ($status) {
                case 'caries':
                case 'root_stump':
                    $decayed++;
                    break;
                case 'missing':
                    $missing++;
                    break;
                case 'filled':
                case 'crown':
                    $filled++;
                    break;
            }
        }

        $dmft = $decayed + $missing + $filled;

        Log::info('DMFT calculated', [
            'decayed' => $decayed,
            'missing' => $missing,
            'filled' => $filled,
            'total' => $dmft,
        ]);

        return [
            'decayed' => $decayed,
            'missing' => $missing,
            'filled' => $filled,
            'total' => $dmft,
        ];
    }

    /**
     * Get common dental diagnoses for autocomplete
     */
    public static function getCommonDiagnoses(): array
    {
        return [
            // Caries
            ['code' => 'K02.1', 'name' => 'Dental caries of dentin'],
            ['code' => 'K02.52', 'name' => 'Dental caries on pit and fissure surface'],
            ['code' => 'K02.61', 'name' => 'Dental caries on smooth surface'],
            
            // Pulp/Periapical
            ['code' => 'K04.0', 'name' => 'Pulpitis'],
            ['code' => 'K04.1', 'name' => 'Necrosis of pulp'],
            ['code' => 'K04.4', 'name' => 'Acute apical periodontitis'],
            ['code' => 'K04.5', 'name' => 'Chronic apical periodontitis'],
            ['code' => 'K04.6', 'name' => 'Periapical abscess with sinus'],
            ['code' => 'K04.7', 'name' => 'Periapical abscess without sinus'],
            
            // Periodontal
            ['code' => 'K05.0', 'name' => 'Acute gingivitis'],
            ['code' => 'K05.1', 'name' => 'Chronic gingivitis'],
            ['code' => 'K05.20', 'name' => 'Aggressive periodontitis'],
            ['code' => 'K05.30', 'name' => 'Chronic periodontitis'],
            ['code' => 'K06.0', 'name' => 'Gingival recession'],
            ['code' => 'K06.1', 'name' => 'Gingival enlargement'],
            
            // Other
            ['code' => 'K00.6', 'name' => 'Disturbances in tooth eruption'],
            ['code' => 'K01.1', 'name' => 'Impacted teeth'],
            ['code' => 'K03.0', 'name' => 'Excessive attrition of teeth'],
            ['code' => 'K03.1', 'name' => 'Abrasion of teeth'],
            ['code' => 'K03.2', 'name' => 'Erosion of teeth'],
            ['code' => 'K07.3', 'name' => 'TMJ disorder'],
            ['code' => 'K08.1', 'name' => 'Loss of teeth due to extraction'],
            ['code' => 'K12.0', 'name' => 'Recurrent oral aphthae'],
            ['code' => 'K12.1', 'name' => 'Stomatitis'],
            ['code' => 'K13.0', 'name' => 'Oral leukoplakia'],
            ['code' => 'S02.5', 'name' => 'Fracture of tooth'],
        ];
    }

    /**
     * Get dental procedure codes for billing
     */
    public static function getProcedureCodes(): array
    {
        return [
            'scaling' => ['code' => 'D1110', 'sac' => '999312', 'description' => 'Prophylaxis - adult'],
            'filling_composite' => ['code' => 'D2391', 'sac' => '999312', 'description' => 'Composite filling'],
            'rct_single' => ['code' => 'D3310', 'sac' => '999312', 'description' => 'Endodontic therapy, anterior'],
            'rct_multi' => ['code' => 'D3330', 'sac' => '999312', 'description' => 'Endodontic therapy, molar'],
            'extraction_simple' => ['code' => 'D7140', 'sac' => '999312', 'description' => 'Extraction, erupted tooth'],
            'extraction_surgical' => ['code' => 'D7210', 'sac' => '999312', 'description' => 'Surgical extraction'],
            'crown_pfc' => ['code' => 'D2751', 'sac' => '999312', 'description' => 'Crown - PFM'],
            'crown_ceramic' => ['code' => 'D2740', 'sac' => '999312', 'description' => 'Crown - ceramic'],
            'implant' => ['code' => 'D6010', 'sac' => '999312', 'description' => 'Surgical implant'],
        ];
    }
}
