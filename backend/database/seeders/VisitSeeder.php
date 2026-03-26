<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VisitSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('VisitSeeder: Starting seeder');
        $now = Carbon::now();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('visits')->truncate();
        DB::table('visit_scales')->truncate();
        DB::table('visit_lesions')->truncate();
        DB::table('visit_procedures')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Get completed appointments
        $completedAppointments = DB::table('appointments')
            ->where('status', 'completed')
            ->get();

        if ($completedAppointments->isEmpty()) {
            $this->command->error('VisitSeeder: No completed appointments found. Run AppointmentSeeder first.');
            Log::error('VisitSeeder: No completed appointments found');
            return;
        }

        $visits = [];
        $visitScales = [];
        $visitLesions = [];
        $visitProcedures = [];

        // Dermatology diagnoses
        $dermaDiagnoses = [
            ['diagnosis' => 'Acne vulgaris', 'icd_code' => 'L70.0'],
            ['diagnosis' => 'Atopic dermatitis', 'icd_code' => 'L20.9'],
            ['diagnosis' => 'Psoriasis vulgaris', 'icd_code' => 'L40.0'],
            ['diagnosis' => 'Melasma', 'icd_code' => 'L81.1'],
            ['diagnosis' => 'Androgenetic alopecia', 'icd_code' => 'L64.0'],
            ['diagnosis' => 'Vitiligo', 'icd_code' => 'L80'],
            ['diagnosis' => 'Seborrheic dermatitis', 'icd_code' => 'L21.9'],
            ['diagnosis' => 'Tinea corporis', 'icd_code' => 'B35.4'],
        ];

        // Physio diagnoses
        $physioDiagnoses = [
            ['diagnosis' => 'Low back pain', 'icd_code' => 'M54.5'],
            ['diagnosis' => 'Cervicalgia', 'icd_code' => 'M54.2'],
            ['diagnosis' => 'Frozen shoulder', 'icd_code' => 'M75.0'],
            ['diagnosis' => 'Knee osteoarthritis', 'icd_code' => 'M17.1'],
            ['diagnosis' => 'Plantar fasciitis', 'icd_code' => 'M72.2'],
            ['diagnosis' => 'ACL injury rehab', 'icd_code' => 'S83.5'],
            ['diagnosis' => 'Cervical spondylosis', 'icd_code' => 'M47.816'],
            ['diagnosis' => 'Rotator cuff syndrome', 'icd_code' => 'M75.1'],
        ];

        // Dental diagnoses
        $dentalDiagnoses = [
            ['diagnosis' => 'Dental caries', 'icd_code' => 'K02.1'],
            ['diagnosis' => 'Chronic gingivitis', 'icd_code' => 'K05.1'],
            ['diagnosis' => 'Pulpitis', 'icd_code' => 'K04.0'],
            ['diagnosis' => 'Periodontitis', 'icd_code' => 'K05.30'],
            ['diagnosis' => 'Malocclusion', 'icd_code' => 'K07.4'],
            ['diagnosis' => 'Impacted tooth', 'icd_code' => 'K01.1'],
            ['diagnosis' => 'Tooth fracture', 'icd_code' => 'S02.5'],
        ];

        $clinic1 = DB::table('clinics')->where('slug', 'sharma-skin-hair')->value('id');
        $clinic2 = DB::table('clinics')->where('slug', 'activephysio-rehab')->value('id');
        $clinic3 = DB::table('clinics')->where('slug', 'brightsmile-dental')->value('id');

        $visitId = 1;
        foreach ($completedAppointments as $appointment) {
            // Determine specialty based on clinic
            if ($appointment->clinic_id == $clinic1) {
                $diagnosisData = $dermaDiagnoses[array_rand($dermaDiagnoses)];
                $specialty = 'dermatology';
            } elseif ($appointment->clinic_id == $clinic2) {
                $diagnosisData = $physioDiagnoses[array_rand($physioDiagnoses)];
                $specialty = 'physiotherapy';
            } else {
                $diagnosisData = $dentalDiagnoses[array_rand($dentalDiagnoses)];
                $specialty = 'dental';
            }

            $chiefComplaint = match ($specialty) {
                'dermatology' => ['Skin rash', 'Itching', 'Hair fall', 'Pigmentation', 'Acne', 'Skin lesion'][rand(0, 5)],
                'physiotherapy' => ['Pain', 'Stiffness', 'Limited mobility', 'Post-op rehab', 'Weakness'][rand(0, 4)],
                'dental' => ['Toothache', 'Bleeding gums', 'Tooth sensitivity', 'Checkup', 'Broken tooth'][rand(0, 4)],
            };

            $visitData = [
                'appointment_id' => $appointment->id,
                'clinic_id' => $appointment->clinic_id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'chief_complaint' => $chiefComplaint,
                'examination_notes' => 'Physical examination performed. Findings noted.',
                'diagnosis' => $diagnosisData['diagnosis'],
                'icd_code' => $diagnosisData['icd_code'],
                'plan' => 'Treatment plan discussed with patient. Follow-up scheduled.',
                'followup_in_days' => [7, 14, 21, 30][rand(0, 3)],
                'emr_data' => json_encode([
                    'specialty' => $specialty,
                    'duration' => rand(10, 30) . ' days',
                    'severity' => ['mild', 'moderate', 'severe'][rand(0, 2)],
                ]),
                'created_at' => $appointment->scheduled_at,
                'updated_at' => $appointment->scheduled_at,
            ];

            $visits[] = $visitData;

            // Add scales for dermatology visits
            if ($specialty === 'dermatology' && rand(0, 1)) {
                $visitScales[] = [
                    'visit_id' => $visitId,
                    'scale_name' => ['PASI', 'DLQI', 'IGA'][rand(0, 2)],
                    'score' => rand(1, 30) / 10,
                    'interpretation' => ['Mild', 'Moderate', 'Severe'][rand(0, 2)],
                    'created_at' => $appointment->scheduled_at,
                    'updated_at' => $appointment->scheduled_at,
                ];
            }

            // Add lesions for some dermatology visits
            if ($specialty === 'dermatology' && rand(0, 2) === 0) {
                $visitLesions[] = [
                    'visit_id' => $visitId,
                    'body_region' => ['face', 'scalp', 'trunk', 'arms', 'legs'][rand(0, 4)],
                    'description' => 'Erythematous papules with scaling',
                    'size_cm' => rand(1, 5) . 'x' . rand(1, 5),
                    'photo_url' => null,
                    'created_at' => $appointment->scheduled_at,
                    'updated_at' => $appointment->scheduled_at,
                ];
            }

            // Add procedures for some visits
            if (rand(0, 2) === 0) {
                $procedureName = match ($specialty) {
                    'dermatology' => ['Chemical Peel', 'Q-Switch Laser', 'PRP', 'Microneedling'][rand(0, 3)],
                    'physiotherapy' => ['Ultrasound', 'TENS', 'Manual Therapy', 'Traction'][rand(0, 3)],
                    'dental' => ['Scaling', 'Filling', 'RCT', 'Extraction'][rand(0, 3)],
                };

                $visitProcedures[] = [
                    'visit_id' => $visitId,
                    'procedure_name' => $procedureName,
                    'notes' => 'Procedure performed successfully',
                    'performed_by' => $appointment->doctor_id,
                    'created_at' => $appointment->scheduled_at,
                    'updated_at' => $appointment->scheduled_at,
                ];
            }

            $visitId++;
        }

        DB::table('visits')->insert($visits);
        
        if (!empty($visitScales)) {
            DB::table('visit_scales')->insert($visitScales);
        }
        
        if (!empty($visitLesions)) {
            DB::table('visit_lesions')->insert($visitLesions);
        }
        
        if (!empty($visitProcedures)) {
            DB::table('visit_procedures')->insert($visitProcedures);
        }

        Log::info('VisitSeeder: Created visits', [
            'visits' => count($visits),
            'scales' => count($visitScales),
            'lesions' => count($visitLesions),
            'procedures' => count($visitProcedures),
        ]);

        $this->command->info('VisitSeeder: created ' . count($visits) . ' visits with ' . count($visitScales) . ' scales, ' . count($visitLesions) . ' lesions, ' . count($visitProcedures) . ' procedures.');
    }
}
