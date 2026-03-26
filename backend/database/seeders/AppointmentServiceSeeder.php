<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AppointmentServiceSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('AppointmentServiceSeeder: Starting seeder');
        $now = Carbon::now();

        $clinic1 = DB::table('clinics')->where('slug', 'sharma-skin-hair')->value('id');
        $clinic2 = DB::table('clinics')->where('slug', 'activephysio-rehab')->value('id');
        $clinic3 = DB::table('clinics')->where('slug', 'brightsmile-dental')->value('id');

        if (!$clinic1 || !$clinic2 || !$clinic3) {
            $this->command->error('AppointmentServiceSeeder: Clinics not found. Run ClinicSeeder first.');
            Log::error('AppointmentServiceSeeder: Clinics not found');
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('appointment_services')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $services = [
            // Sharma Skin & Hair Clinic (Dermatology)
            [
                'clinic_id' => $clinic1,
                'name' => 'Consultation',
                'duration_mins' => 20,
                'default_price' => 800.00,
                'sac_code' => '999311',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic1,
                'name' => 'Follow-up Consultation',
                'duration_mins' => 15,
                'default_price' => 500.00,
                'sac_code' => '999311',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic1,
                'name' => 'Chemical Peel',
                'duration_mins' => 45,
                'default_price' => 3500.00,
                'sac_code' => '999312',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic1,
                'name' => 'Q-Switch Laser',
                'duration_mins' => 30,
                'default_price' => 4500.00,
                'sac_code' => '999312',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic1,
                'name' => 'PRP Hair Treatment',
                'duration_mins' => 60,
                'default_price' => 8000.00,
                'sac_code' => '999312',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic1,
                'name' => 'Laser Hair Removal (Full Face)',
                'duration_mins' => 30,
                'default_price' => 3000.00,
                'sac_code' => '999312',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic1,
                'name' => 'Botox (Per Unit)',
                'duration_mins' => 30,
                'default_price' => 350.00,
                'sac_code' => '999312',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ActivePhysio & Rehab (Physiotherapy)
            [
                'clinic_id' => $clinic2,
                'name' => 'Initial Assessment',
                'duration_mins' => 45,
                'default_price' => 1000.00,
                'sac_code' => '998111',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic2,
                'name' => 'Physiotherapy Session',
                'duration_mins' => 45,
                'default_price' => 800.00,
                'sac_code' => '998111',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic2,
                'name' => 'Post-Surgical Rehab',
                'duration_mins' => 60,
                'default_price' => 1200.00,
                'sac_code' => '998111',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic2,
                'name' => 'Dry Needling',
                'duration_mins' => 30,
                'default_price' => 600.00,
                'sac_code' => '998111',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic2,
                'name' => 'Traction Session',
                'duration_mins' => 30,
                'default_price' => 500.00,
                'sac_code' => '998111',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic2,
                'name' => 'Sports Rehab Session',
                'duration_mins' => 60,
                'default_price' => 1500.00,
                'sac_code' => '998111',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // BrightSmile Dental (Dental)
            [
                'clinic_id' => $clinic3,
                'name' => 'Dental Consultation',
                'duration_mins' => 20,
                'default_price' => 500.00,
                'sac_code' => '999322',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic3,
                'name' => 'Scaling & Polishing',
                'duration_mins' => 30,
                'default_price' => 1500.00,
                'sac_code' => '999322',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic3,
                'name' => 'Filling (Composite)',
                'duration_mins' => 30,
                'default_price' => 1200.00,
                'sac_code' => '999322',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic3,
                'name' => 'Root Canal Treatment',
                'duration_mins' => 60,
                'default_price' => 6000.00,
                'sac_code' => '999322',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic3,
                'name' => 'Extraction (Simple)',
                'duration_mins' => 30,
                'default_price' => 1000.00,
                'sac_code' => '999322',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic3,
                'name' => 'Crown (PFM)',
                'duration_mins' => 45,
                'default_price' => 5000.00,
                'sac_code' => '999322',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic3,
                'name' => 'Crown (Zirconia)',
                'duration_mins' => 45,
                'default_price' => 12000.00,
                'sac_code' => '999322',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic3,
                'name' => 'Dental Implant',
                'duration_mins' => 90,
                'default_price' => 35000.00,
                'sac_code' => '999322',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic3,
                'name' => 'Teeth Whitening',
                'duration_mins' => 60,
                'default_price' => 8000.00,
                'sac_code' => '999312',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic3,
                'name' => 'Orthodontic Consultation',
                'duration_mins' => 30,
                'default_price' => 700.00,
                'sac_code' => '999322',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('appointment_services')->insert($services);

        Log::info('AppointmentServiceSeeder: Created ' . count($services) . ' services');
        $this->command->info('AppointmentServiceSeeder: created ' . count($services) . ' services across 3 clinics.');
    }
}
