<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Fetch clinics by slug for stable referencing
        $clinic1 = DB::table('clinics')->where('slug', 'sharma-skin-hair')->value('id');
        $clinic2 = DB::table('clinics')->where('slug', 'activephysio-rehab')->value('id');
        $clinic3 = DB::table('clinics')->where('slug', 'brightsmile-dental')->value('id');

        if (!$clinic1 || !$clinic2 || !$clinic3) {
            $this->command->error('UserSeeder: Clinics not found. Run ClinicSeeder first.');
            return;
        }

        // Truncate users (FK safe: seeders run in order)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $password = Hash::make('password');

        $users = [
            // ── Sharma Skin & Hair Clinic (clinic 1) ─────────────────────────
            [
                'clinic_id'           => $clinic1,
                'name'                => 'Dr. Priya Sharma',
                'email'               => 'priya.sharma@sharmaskin.in',
                'phone'               => '+919876543210',
                'password'            => $password,
                'role'                => 'owner',
                'specialty'           => 'dermatology',
                'hpr_id'              => 'HPR-MH-2024-10001',
                'qualification'       => 'MD (Dermatology), FAAD',
                'registration_number' => 'MMC-2010-12345',
                'is_active'           => true,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'clinic_id'           => $clinic1,
                'name'                => 'Neha Joshi',
                'email'               => 'neha.joshi@sharmaskin.in',
                'phone'               => '+919765432100',
                'password'            => $password,
                'role'                => 'receptionist',
                'specialty'           => null,
                'hpr_id'              => null,
                'qualification'       => 'B.Com, Medical Office Management Diploma',
                'registration_number' => null,
                'is_active'           => true,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'clinic_id'           => $clinic1,
                'name'                => 'Monika Patil',
                'email'               => 'monika.patil@sharmaskin.in',
                'phone'               => '+919654321000',
                'password'            => $password,
                'role'                => 'staff',
                'specialty'           => null,
                'hpr_id'              => null,
                'qualification'       => 'GNM (Nursing)',
                'registration_number' => 'MNC-2018-67890',
                'is_active'           => true,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],

            // ── ActivePhysio & Rehab Centre (clinic 2) ───────────────────────
            [
                'clinic_id'           => $clinic2,
                'name'                => 'Dr. Arjun Mehta',
                'email'               => 'arjun.mehta@activephysio.in',
                'phone'               => '+919988776655',
                'password'            => $password,
                'role'                => 'owner',
                'specialty'           => 'physiotherapy',
                'hpr_id'              => 'HPR-MH-2024-20001',
                'qualification'       => 'BPT, MPT (Musculoskeletal)',
                'registration_number' => 'MCPT-MH-2015-5678',
                'is_active'           => true,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'clinic_id'           => $clinic2,
                'name'                => 'Dr. Sunita Rao',
                'email'               => 'sunita.rao@activephysio.in',
                'phone'               => '+919876501234',
                'password'            => $password,
                'role'                => 'doctor',
                'specialty'           => 'physiotherapy',
                'hpr_id'              => 'HPR-MH-2024-20002',
                'qualification'       => 'BPT, MPT (Sports & Exercise)',
                'registration_number' => 'MCPT-MH-2017-7890',
                'is_active'           => true,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'clinic_id'           => $clinic2,
                'name'                => 'Ramesh Kumar',
                'email'               => 'ramesh.kumar@activephysio.in',
                'phone'               => '+919765012345',
                'password'            => $password,
                'role'                => 'receptionist',
                'specialty'           => null,
                'hpr_id'              => null,
                'qualification'       => 'B.Sc, Front Office Management',
                'registration_number' => null,
                'is_active'           => true,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],

            // ── BrightSmile Dental Studio (clinic 3) ─────────────────────────
            [
                'clinic_id'           => $clinic3,
                'name'                => 'Dr. Kavitha Reddy',
                'email'               => 'kavitha.reddy@brightsmile.dental',
                'phone'               => '+919845001234',
                'password'            => $password,
                'role'                => 'owner',
                'specialty'           => 'dental',
                'hpr_id'              => 'HPR-KA-2024-30001',
                'qualification'       => 'BDS, MDS (Prosthodontics)',
                'registration_number' => 'KDA-2012-34567',
                'is_active'           => true,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'clinic_id'           => $clinic3,
                'name'                => 'Dr. Vikram Nair',
                'email'               => 'vikram.nair@brightsmile.dental',
                'phone'               => '+919844001234',
                'password'            => $password,
                'role'                => 'doctor',
                'specialty'           => 'dental',
                'hpr_id'              => 'HPR-KA-2024-30002',
                'qualification'       => 'BDS, MDS (Orthodontics)',
                'registration_number' => 'KDA-2016-45678',
                'is_active'           => true,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'clinic_id'           => $clinic3,
                'name'                => 'Ananya Singh',
                'email'               => 'ananya.singh@brightsmile.dental',
                'phone'               => '+919743001234',
                'password'            => $password,
                'role'                => 'receptionist',
                'specialty'           => null,
                'hpr_id'              => null,
                'qualification'       => 'BA, Diploma in Hospital Administration',
                'registration_number' => null,
                'is_active'           => true,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
        ];

        DB::table('users')->insert($users);

        $this->command->info('UserSeeder: created ' . count($users) . ' users across 3 clinics.');
    }
}
