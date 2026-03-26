<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('AppointmentSeeder: Starting seeder');
        $now = Carbon::now();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('appointments')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Get clinics
        $clinic1 = DB::table('clinics')->where('slug', 'sharma-skin-hair')->value('id');
        $clinic2 = DB::table('clinics')->where('slug', 'activephysio-rehab')->value('id');
        $clinic3 = DB::table('clinics')->where('slug', 'brightsmile-dental')->value('id');

        if (!$clinic1 || !$clinic2 || !$clinic3) {
            $this->command->error('AppointmentSeeder: Clinics not found.');
            Log::error('AppointmentSeeder: Clinics not found');
            return;
        }

        // Get doctors and patients per clinic
        $doctor1 = DB::table('users')->where('clinic_id', $clinic1)->where('role', 'owner')->value('id');
        $doctor2 = DB::table('users')->where('clinic_id', $clinic2)->where('role', 'owner')->value('id');
        $doctor2b = DB::table('users')->where('clinic_id', $clinic2)->where('role', 'doctor')->value('id');
        $doctor3 = DB::table('users')->where('clinic_id', $clinic3)->where('role', 'owner')->value('id');
        $doctor3b = DB::table('users')->where('clinic_id', $clinic3)->where('role', 'doctor')->value('id');

        $patients1 = DB::table('patients')->where('clinic_id', $clinic1)->pluck('id')->toArray();
        $patients2 = DB::table('patients')->where('clinic_id', $clinic2)->pluck('id')->toArray();
        $patients3 = DB::table('patients')->where('clinic_id', $clinic3)->pluck('id')->toArray();

        $services1 = DB::table('appointment_services')->where('clinic_id', $clinic1)->pluck('id')->toArray();
        $services2 = DB::table('appointment_services')->where('clinic_id', $clinic2)->pluck('id')->toArray();
        $services3 = DB::table('appointment_services')->where('clinic_id', $clinic3)->pluck('id')->toArray();

        $appointments = [];
        $today = Carbon::today();

        // Past appointments (last 30 days) - completed
        for ($i = 1; $i <= 30; $i++) {
            $date = $today->copy()->subDays($i);
            if ($date->isWeekend()) continue;

            // Clinic 1 - 3-4 appointments per day
            for ($j = 0; $j < rand(3, 4); $j++) {
                $hour = 9 + ($j * 1);
                $appointments[] = [
                    'clinic_id' => $clinic1,
                    'patient_id' => $patients1[array_rand($patients1)],
                    'doctor_id' => $doctor1,
                    'service_id' => $services1[array_rand($services1)],
                    'scheduled_at' => $date->copy()->setTime($hour, rand(0, 1) * 30, 0),
                    'duration_mins' => [15, 20, 30][rand(0, 2)],
                    'status' => 'completed',
                    'check_in_at' => $date->copy()->setTime($hour - 1, 50, 0),
                    'notes' => null,
                    'created_at' => $date->copy()->subDays(rand(1, 7)),
                    'updated_at' => $date,
                ];
            }

            // Clinic 2 - 2-3 appointments per day
            for ($j = 0; $j < rand(2, 3); $j++) {
                $hour = 9 + ($j * 1);
                $doctorId = rand(0, 1) ? $doctor2 : ($doctor2b ?? $doctor2);
                $appointments[] = [
                    'clinic_id' => $clinic2,
                    'patient_id' => $patients2[array_rand($patients2)],
                    'doctor_id' => $doctorId,
                    'service_id' => $services2[array_rand($services2)],
                    'scheduled_at' => $date->copy()->setTime($hour, rand(0, 1) * 30, 0),
                    'duration_mins' => [30, 45, 60][rand(0, 2)],
                    'status' => 'completed',
                    'check_in_at' => $date->copy()->setTime($hour - 1, 55, 0),
                    'notes' => null,
                    'created_at' => $date->copy()->subDays(rand(1, 7)),
                    'updated_at' => $date,
                ];
            }

            // Clinic 3 - 3-5 appointments per day
            for ($j = 0; $j < rand(3, 5); $j++) {
                $hour = 10 + intval($j * 0.5);
                $doctorId = rand(0, 1) ? $doctor3 : ($doctor3b ?? $doctor3);
                $appointments[] = [
                    'clinic_id' => $clinic3,
                    'patient_id' => $patients3[array_rand($patients3)],
                    'doctor_id' => $doctorId,
                    'service_id' => $services3[array_rand($services3)],
                    'scheduled_at' => $date->copy()->setTime($hour, rand(0, 1) * 30, 0),
                    'duration_mins' => [20, 30, 45][rand(0, 2)],
                    'status' => 'completed',
                    'check_in_at' => $date->copy()->setTime($hour - 1, 45, 0),
                    'notes' => null,
                    'created_at' => $date->copy()->subDays(rand(1, 14)),
                    'updated_at' => $date,
                ];
            }
        }

        // Today's appointments - mix of statuses
        $todayAppointments = [
            // Clinic 1 today
            [
                'clinic_id' => $clinic1,
                'patient_id' => $patients1[0],
                'doctor_id' => $doctor1,
                'service_id' => $services1[0],
                'scheduled_at' => $today->copy()->setTime(9, 0, 0),
                'duration_mins' => 20,
                'status' => 'completed',
                'check_in_at' => $today->copy()->setTime(8, 50, 0),
                'notes' => 'Regular follow-up',
                'created_at' => $today->copy()->subDays(3),
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic1,
                'patient_id' => $patients1[1],
                'doctor_id' => $doctor1,
                'service_id' => $services1[2],
                'scheduled_at' => $today->copy()->setTime(9, 30, 0),
                'duration_mins' => 45,
                'status' => 'in_progress',
                'check_in_at' => $today->copy()->setTime(9, 25, 0),
                'notes' => 'Chemical peel session 2',
                'created_at' => $today->copy()->subDays(7),
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic1,
                'patient_id' => $patients1[2],
                'doctor_id' => $doctor1,
                'service_id' => $services1[0],
                'scheduled_at' => $today->copy()->setTime(10, 30, 0),
                'duration_mins' => 20,
                'status' => 'checked_in',
                'check_in_at' => $today->copy()->setTime(10, 20, 0),
                'notes' => null,
                'created_at' => $today->copy()->subDays(2),
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic1,
                'patient_id' => $patients1[3],
                'doctor_id' => $doctor1,
                'service_id' => $services1[3],
                'scheduled_at' => $today->copy()->setTime(11, 0, 0),
                'duration_mins' => 30,
                'status' => 'booked',
                'check_in_at' => null,
                'notes' => 'Q-Switch for pigmentation',
                'created_at' => $today->copy()->subDays(5),
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic1,
                'patient_id' => $patients1[4],
                'doctor_id' => $doctor1,
                'service_id' => $services1[4],
                'scheduled_at' => $today->copy()->setTime(11, 30, 0),
                'duration_mins' => 60,
                'status' => 'booked',
                'check_in_at' => null,
                'notes' => 'PRP session 3 of 6',
                'created_at' => $today->copy()->subDays(1),
                'updated_at' => $now,
            ],

            // Clinic 2 today
            [
                'clinic_id' => $clinic2,
                'patient_id' => $patients2[0],
                'doctor_id' => $doctor2,
                'service_id' => $services2[1],
                'scheduled_at' => $today->copy()->setTime(9, 0, 0),
                'duration_mins' => 45,
                'status' => 'completed',
                'check_in_at' => $today->copy()->setTime(8, 55, 0),
                'notes' => 'Knee OA - session 10',
                'created_at' => $today->copy()->subDays(2),
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic2,
                'patient_id' => $patients2[1],
                'doctor_id' => $doctor2,
                'service_id' => $services2[1],
                'scheduled_at' => $today->copy()->setTime(10, 0, 0),
                'duration_mins' => 45,
                'status' => 'in_progress',
                'check_in_at' => $today->copy()->setTime(9, 55, 0),
                'notes' => 'Frozen shoulder - session 5',
                'created_at' => $today->copy()->subDays(4),
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic2,
                'patient_id' => $patients2[2],
                'doctor_id' => $doctor2b ?? $doctor2,
                'service_id' => $services2[2],
                'scheduled_at' => $today->copy()->setTime(11, 0, 0),
                'duration_mins' => 60,
                'status' => 'booked',
                'check_in_at' => null,
                'notes' => 'Post-op L4-L5',
                'created_at' => $today->copy()->subDays(7),
                'updated_at' => $now,
            ],

            // Clinic 3 today
            [
                'clinic_id' => $clinic3,
                'patient_id' => $patients3[0],
                'doctor_id' => $doctor3,
                'service_id' => $services3[1],
                'scheduled_at' => $today->copy()->setTime(10, 0, 0),
                'duration_mins' => 30,
                'status' => 'completed',
                'check_in_at' => $today->copy()->setTime(9, 50, 0),
                'notes' => 'Routine scaling',
                'created_at' => $today->copy()->subDays(7),
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic3,
                'patient_id' => $patients3[1],
                'doctor_id' => $doctor3b ?? $doctor3,
                'service_id' => $services3[7],
                'scheduled_at' => $today->copy()->setTime(10, 30, 0),
                'duration_mins' => 30,
                'status' => 'in_progress',
                'check_in_at' => $today->copy()->setTime(10, 25, 0),
                'notes' => 'Ortho adjustment',
                'created_at' => $today->copy()->subDays(14),
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic3,
                'patient_id' => $patients3[2],
                'doctor_id' => $doctor3,
                'service_id' => $services3[3],
                'scheduled_at' => $today->copy()->setTime(11, 30, 0),
                'duration_mins' => 60,
                'status' => 'booked',
                'check_in_at' => null,
                'notes' => 'RCT - molar',
                'created_at' => $today->copy()->subDays(3),
                'updated_at' => $now,
            ],
        ];

        $appointments = array_merge($appointments, $todayAppointments);

        // Future appointments (next 7 days)
        for ($i = 1; $i <= 7; $i++) {
            $date = $today->copy()->addDays($i);
            if ($date->isWeekend()) continue;

            // Clinic 1 - 2-3 future appointments
            for ($j = 0; $j < rand(2, 3); $j++) {
                $hour = 9 + ($j * 2);
                $appointments[] = [
                    'clinic_id' => $clinic1,
                    'patient_id' => $patients1[array_rand($patients1)],
                    'doctor_id' => $doctor1,
                    'service_id' => $services1[array_rand($services1)],
                    'scheduled_at' => $date->copy()->setTime($hour, 0, 0),
                    'duration_mins' => [15, 20, 30][rand(0, 2)],
                    'status' => 'booked',
                    'check_in_at' => null,
                    'notes' => null,
                    'created_at' => $now->copy()->subDays(rand(1, 5)),
                    'updated_at' => $now,
                ];
            }

            // Clinic 2 - 1-2 future appointments
            for ($j = 0; $j < rand(1, 2); $j++) {
                $hour = 9 + ($j * 2);
                $appointments[] = [
                    'clinic_id' => $clinic2,
                    'patient_id' => $patients2[array_rand($patients2)],
                    'doctor_id' => rand(0, 1) ? $doctor2 : ($doctor2b ?? $doctor2),
                    'service_id' => $services2[array_rand($services2)],
                    'scheduled_at' => $date->copy()->setTime($hour, 0, 0),
                    'duration_mins' => [30, 45, 60][rand(0, 2)],
                    'status' => 'booked',
                    'check_in_at' => null,
                    'notes' => null,
                    'created_at' => $now->copy()->subDays(rand(1, 3)),
                    'updated_at' => $now,
                ];
            }

            // Clinic 3 - 2-4 future appointments
            for ($j = 0; $j < rand(2, 4); $j++) {
                $hour = 10 + $j;
                $appointments[] = [
                    'clinic_id' => $clinic3,
                    'patient_id' => $patients3[array_rand($patients3)],
                    'doctor_id' => rand(0, 1) ? $doctor3 : ($doctor3b ?? $doctor3),
                    'service_id' => $services3[array_rand($services3)],
                    'scheduled_at' => $date->copy()->setTime($hour, 0, 0),
                    'duration_mins' => [20, 30, 45][rand(0, 2)],
                    'status' => 'booked',
                    'check_in_at' => null,
                    'notes' => null,
                    'created_at' => $now->copy()->subDays(rand(1, 5)),
                    'updated_at' => $now,
                ];
            }
        }

        // Add a few cancelled/no-show appointments
        $appointments[] = [
            'clinic_id' => $clinic1,
            'patient_id' => $patients1[5],
            'doctor_id' => $doctor1,
            'service_id' => $services1[0],
            'scheduled_at' => $today->copy()->subDays(2)->setTime(10, 0, 0),
            'duration_mins' => 20,
            'status' => 'cancelled',
            'check_in_at' => null,
            'notes' => 'Patient cancelled - rescheduled',
            'created_at' => $today->copy()->subDays(10),
            'updated_at' => $today->copy()->subDays(3),
        ];

        $appointments[] = [
            'clinic_id' => $clinic2,
            'patient_id' => $patients2[3],
            'doctor_id' => $doctor2,
            'service_id' => $services2[1],
            'scheduled_at' => $today->copy()->subDays(5)->setTime(9, 0, 0),
            'duration_mins' => 45,
            'status' => 'no_show',
            'check_in_at' => null,
            'notes' => 'Did not show up',
            'created_at' => $today->copy()->subDays(12),
            'updated_at' => $today->copy()->subDays(5),
        ];

        DB::table('appointments')->insert($appointments);

        Log::info('AppointmentSeeder: Created appointments', ['count' => count($appointments)]);
        $this->command->info('AppointmentSeeder: created ' . count($appointments) . ' appointments across 3 clinics.');
    }
}
