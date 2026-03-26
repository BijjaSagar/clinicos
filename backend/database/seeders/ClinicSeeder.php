<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ── Truncate dependents in correct order before re-seeding ──
        // (idempotent: safe to run multiple times)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('clinic_locations')->truncate();
        DB::table('clinics')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Clinic 1: Sharma Skin & Hair Clinic — Pune ──────────────
        $clinic1 = DB::table('clinics')->insertGetId([
            'name'                  => 'Sharma Skin & Hair Clinic',
            'slug'                  => 'sharma-skin-hair',
            'plan'                  => 'small',
            'specialties'           => json_encode(['dermatology']),
            'gstin'                 => '27AADCS1234A1Z5',
            'hfr_id'                => 'HFR-MH-2024-00123',
            'hfr_facility_id'       => 'FACILITY-MH-0023',
            'razorpay_account_id'   => 'acc_test_sharma001',
            'whatsapp_phone_id'     => 'WA-MH-91-9876543210',
            'city'                  => 'Pune',
            'state'                 => 'Maharashtra',
            'abdm_active'           => true,
            'settings'              => json_encode([
                'timezone'              => 'Asia/Kolkata',
                'currency'              => 'INR',
                'invoice_prefix'        => 'SSHC',
                'default_slot_mins'     => 20,
                'bank_name'             => 'HDFC Bank',
                'bank_account_number'   => '50100123456789',
                'bank_ifsc'             => 'HDFC0001234',
                'bank_account_name'     => 'Sharma Skin & Hair Clinic',
                'razorpay_key_id'       => 'rzp_test_sharma001key',
                'razorpay_key_secret'   => 'rzp_test_secret_sharma001',
                'logo_url'              => 'https://assets.clinicos.in/logos/sharma-skin-hair.png',
                'address_line1'         => '302, Aditya Heights, FC Road',
                'address_line2'         => 'Shivajinagar',
                'city'                  => 'Pune',
                'state'                 => 'Maharashtra',
                'pincode'               => '411005',
                'phone'                 => '+91-20-25678901',
                'email'                 => 'info@sharmaskin.in',
                'website'               => 'https://www.sharmaskin.in',
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Clinic 1 — main location
        DB::table('clinic_locations')->insert([
            'clinic_id'     => $clinic1,
            'name'          => 'FC Road Branch',
            'address_line1' => '302, Aditya Heights, FC Road',
            'address_line2' => 'Shivajinagar',
            'city'          => 'Pune',
            'state'         => 'Maharashtra',
            'pincode'       => '411005',
            'phone'         => '+91-20-25678901',
            'email'         => 'fcroadbranch@sharmaskin.in',
            'is_primary'    => true,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        // ── Clinic 2: ActivePhysio & Rehab Centre — Mumbai ──────────
        $clinic2 = DB::table('clinics')->insertGetId([
            'name'                  => 'ActivePhysio & Rehab Centre',
            'slug'                  => 'activephysio-rehab',
            'plan'                  => 'solo',
            'specialties'           => json_encode(['physiotherapy', 'rehabilitation']),
            'gstin'                 => '27AACCA5678B1Z2',
            'hfr_id'                => 'HFR-MH-2024-00456',
            'hfr_facility_id'       => 'FACILITY-MH-0056',
            'razorpay_account_id'   => 'acc_test_activephysio002',
            'whatsapp_phone_id'     => 'WA-MH-91-9988776655',
            'city'                  => 'Mumbai',
            'state'                 => 'Maharashtra',
            'abdm_active'           => true,
            'settings'              => json_encode([
                'timezone'              => 'Asia/Kolkata',
                'currency'              => 'INR',
                'invoice_prefix'        => 'APRC',
                'default_slot_mins'     => 30,
                'bank_name'             => 'ICICI Bank',
                'bank_account_number'   => '001234567890',
                'bank_ifsc'             => 'ICIC0001234',
                'bank_account_name'     => 'ActivePhysio & Rehab Centre',
                'razorpay_key_id'       => 'rzp_test_activephysio002key',
                'razorpay_key_secret'   => 'rzp_test_secret_activephysio002',
                'logo_url'              => 'https://assets.clinicos.in/logos/activephysio-rehab.png',
                'address_line1'         => '14, Suncity Tower, Andheri West',
                'address_line2'         => 'Near Andheri Station',
                'city'                  => 'Mumbai',
                'state'                 => 'Maharashtra',
                'pincode'               => '400058',
                'phone'                 => '+91-22-26789012',
                'email'                 => 'info@activephysio.in',
                'website'               => 'https://www.activephysio.in',
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('clinic_locations')->insert([
            'clinic_id'     => $clinic2,
            'name'          => 'Andheri West Branch',
            'address_line1' => '14, Suncity Tower, Andheri West',
            'address_line2' => 'Near Andheri Station',
            'city'          => 'Mumbai',
            'state'         => 'Maharashtra',
            'pincode'       => '400058',
            'phone'         => '+91-22-26789012',
            'email'         => 'andheri@activephysio.in',
            'is_primary'    => true,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        // ── Clinic 3: BrightSmile Dental Studio — Bangalore ─────────
        $clinic3 = DB::table('clinics')->insertGetId([
            'name'                  => 'BrightSmile Dental Studio',
            'slug'                  => 'brightsmile-dental',
            'plan'                  => 'group',
            'specialties'           => json_encode(['dental', 'orthodontics']),
            'gstin'                 => '29AABCB9012C1Z8',
            'hfr_id'                => 'HFR-KA-2024-00789',
            'hfr_facility_id'       => 'FACILITY-KA-0089',
            'razorpay_account_id'   => 'acc_test_brightsmile003',
            'whatsapp_phone_id'     => 'WA-KA-91-9845001234',
            'city'                  => 'Bangalore',
            'state'                 => 'Karnataka',
            'abdm_active'           => false,
            'settings'              => json_encode([
                'timezone'              => 'Asia/Kolkata',
                'currency'              => 'INR',
                'invoice_prefix'        => 'BSDS',
                'default_slot_mins'     => 15,
                'bank_name'             => 'Axis Bank',
                'bank_account_number'   => '9876543210012',
                'bank_ifsc'             => 'UTIB0001234',
                'bank_account_name'     => 'BrightSmile Dental Studio LLP',
                'razorpay_key_id'       => 'rzp_test_brightsmile003key',
                'razorpay_key_secret'   => 'rzp_test_secret_brightsmile003',
                'logo_url'              => 'https://assets.clinicos.in/logos/brightsmile-dental.png',
                'address_line1'         => '7, Brigade Road, Ground Floor',
                'address_line2'         => 'Opp. Total Mall, Koramangala',
                'city'                  => 'Bangalore',
                'state'                 => 'Karnataka',
                'pincode'               => '560034',
                'phone'                 => '+91-80-41234567',
                'email'                 => 'hello@brightsmile.dental',
                'website'               => 'https://www.brightsmile.dental',
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('clinic_locations')->insert([
            [
                'clinic_id'     => $clinic3,
                'name'          => 'Koramangala Branch',
                'address_line1' => '7, Brigade Road, Ground Floor',
                'address_line2' => 'Opp. Total Mall, Koramangala',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'pincode'       => '560034',
                'phone'         => '+91-80-41234567',
                'email'         => 'koramangala@brightsmile.dental',
                'is_primary'    => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'clinic_id'     => $clinic3,
                'name'          => 'Indiranagar Branch',
                'address_line1' => '12A, 100 Feet Road',
                'address_line2' => 'Indiranagar, 1st Stage',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'pincode'       => '560038',
                'phone'         => '+91-80-42345678',
                'email'         => 'indiranagar@brightsmile.dental',
                'is_primary'    => false,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ]);

        $this->command->info("ClinicSeeder: created 3 clinics (IDs: {$clinic1}, {$clinic2}, {$clinic3}) with locations.");
    }
}
