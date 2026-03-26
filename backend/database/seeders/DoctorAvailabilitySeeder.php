<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DoctorAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        Log::info('DoctorAvailabilitySeeder: Starting seeder');
        $now = Carbon::now();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('doctor_availability')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Get doctors
        $doctors = DB::table('users')
            ->whereIn('role', ['owner', 'doctor'])
            ->whereNotNull('specialty')
            ->get();

        if ($doctors->isEmpty()) {
            $this->command->error('DoctorAvailabilitySeeder: No doctors found. Run UserSeeder first.');
            Log::error('DoctorAvailabilitySeeder: No doctors found');
            return;
        }

        $availability = [];
        $dayNames = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        foreach ($doctors as $doctor) {
            Log::info('DoctorAvailabilitySeeder: Setting availability for doctor', ['doctor_id' => $doctor->id, 'name' => $doctor->name]);

            foreach ($dayNames as $day) {
                // Morning slot
                $availability[] = [
                    'clinic_id' => $doctor->clinic_id,
                    'user_id' => $doctor->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00:00',
                    'end_time' => '13:00:00',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Evening slot (except Saturday for some doctors)
                if (!($day === 'saturday' && rand(0, 1) === 0)) {
                    $availability[] = [
                        'clinic_id' => $doctor->clinic_id,
                        'user_id' => $doctor->id,
                        'day_of_week' => $day,
                        'start_time' => '17:00:00',
                        'end_time' => '20:00:00',
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        DB::table('doctor_availability')->insert($availability);

        Log::info('DoctorAvailabilitySeeder: Created availability records', ['count' => count($availability)]);
        $this->command->info('DoctorAvailabilitySeeder: created ' . count($availability) . ' availability slots for ' . count($doctors) . ' doctors.');
    }
}
