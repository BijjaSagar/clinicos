<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('VendorSeeder: Starting seeder');
        $now = Carbon::now();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('vendor_labs')->truncate();
        DB::table('lab_test_catalog')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Get clinics
        $clinic1 = DB::table('clinics')->where('slug', 'sharma-skin-hair')->value('id');
        $clinic2 = DB::table('clinics')->where('slug', 'activephysio-rehab')->value('id');
        $clinic3 = DB::table('clinics')->where('slug', 'brightsmile-dental')->value('id');

        // Vendor Labs
        $vendorLabs = [
            [
                'clinic_id' => $clinic1,
                'name' => 'Dr. Lal PathLabs',
                'type' => 'pathology',
                'contact_name' => 'Ravi Kumar',
                'contact_phone' => '+919876012345',
                'contact_email' => 'ravi@lalpathlab.com',
                'address' => '123, MG Road, Pune',
                'is_active' => true,
                'settings' => json_encode(['pickup_available' => true, 'report_turnaround_hours' => 24]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic1,
                'name' => 'Thyrocare',
                'type' => 'pathology',
                'contact_name' => 'Priya Sharma',
                'contact_phone' => '+919876012346',
                'contact_email' => 'priya@thyrocare.com',
                'address' => '456, FC Road, Pune',
                'is_active' => true,
                'settings' => json_encode(['home_collection' => true, 'report_turnaround_hours' => 48]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic2,
                'name' => 'SRL Diagnostics',
                'type' => 'pathology',
                'contact_name' => 'Amit Patel',
                'contact_phone' => '+919876012347',
                'contact_email' => 'amit@srl.com',
                'address' => '789, Andheri West, Mumbai',
                'is_active' => true,
                'settings' => json_encode(['pickup_available' => true, 'report_turnaround_hours' => 24]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic3,
                'name' => 'Sai Dental Lab',
                'type' => 'dental_lab',
                'contact_name' => 'Vinod Kumar',
                'contact_phone' => '+919876012348',
                'contact_email' => 'vinod@saidentallab.com',
                'address' => '101, Brigade Road, Bangalore',
                'is_active' => true,
                'settings' => json_encode(['pickup_available' => true, 'turnaround_days' => 5]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => $clinic3,
                'name' => 'Perfect Dental Solutions',
                'type' => 'dental_lab',
                'contact_name' => 'Ramesh Gupta',
                'contact_phone' => '+919876012349',
                'contact_email' => 'ramesh@perfectdental.com',
                'address' => '202, Koramangala, Bangalore',
                'is_active' => true,
                'settings' => json_encode(['pickup_available' => false, 'turnaround_days' => 7]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('vendor_labs')->insert($vendorLabs);

        // Lab Test Catalog
        $labTests = [
            // Pathology tests
            ['name' => 'Complete Blood Count (CBC)', 'code' => 'CBC', 'category' => 'hematology', 'default_price' => 450.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Liver Function Test (LFT)', 'code' => 'LFT', 'category' => 'biochemistry', 'default_price' => 800.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kidney Function Test (KFT)', 'code' => 'KFT', 'category' => 'biochemistry', 'default_price' => 700.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Lipid Profile', 'code' => 'LIPID', 'category' => 'biochemistry', 'default_price' => 650.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Thyroid Profile (T3, T4, TSH)', 'code' => 'THYROID', 'category' => 'endocrinology', 'default_price' => 750.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'HbA1c', 'code' => 'HBA1C', 'category' => 'diabetes', 'default_price' => 500.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Fasting Blood Sugar', 'code' => 'FBS', 'category' => 'diabetes', 'default_price' => 100.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Vitamin D', 'code' => 'VITD', 'category' => 'vitamins', 'default_price' => 1200.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Vitamin B12', 'code' => 'VITB12', 'category' => 'vitamins', 'default_price' => 800.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Iron Profile', 'code' => 'IRON', 'category' => 'hematology', 'default_price' => 600.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ANA (Antinuclear Antibody)', 'code' => 'ANA', 'category' => 'immunology', 'default_price' => 1500.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ESR (Erythrocyte Sedimentation Rate)', 'code' => 'ESR', 'category' => 'hematology', 'default_price' => 150.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'CRP (C-Reactive Protein)', 'code' => 'CRP', 'category' => 'immunology', 'default_price' => 400.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'RA Factor', 'code' => 'RAF', 'category' => 'immunology', 'default_price' => 500.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Uric Acid', 'code' => 'URIC', 'category' => 'biochemistry', 'default_price' => 200.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            
            // Dermatology specific
            ['name' => 'KOH Mount (Fungal)', 'code' => 'KOH', 'category' => 'dermatology', 'default_price' => 300.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Skin Biopsy', 'code' => 'SKINBX', 'category' => 'dermatology', 'default_price' => 2500.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Patch Test', 'code' => 'PATCH', 'category' => 'dermatology', 'default_price' => 3500.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tzanck Smear', 'code' => 'TZANCK', 'category' => 'dermatology', 'default_price' => 400.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            
            // Radiology
            ['name' => 'X-Ray Chest PA View', 'code' => 'XCHEST', 'category' => 'radiology', 'default_price' => 400.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'X-Ray Spine (LS)', 'code' => 'XLSPINE', 'category' => 'radiology', 'default_price' => 500.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'X-Ray Spine (Cervical)', 'code' => 'XCSPINE', 'category' => 'radiology', 'default_price' => 500.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'X-Ray Knee (Both)', 'code' => 'XKNEE', 'category' => 'radiology', 'default_price' => 600.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'MRI Lumbar Spine', 'code' => 'MRILS', 'category' => 'radiology', 'default_price' => 8000.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'MRI Cervical Spine', 'code' => 'MRICS', 'category' => 'radiology', 'default_price' => 8000.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'MRI Knee', 'code' => 'MRIKNEE', 'category' => 'radiology', 'default_price' => 7000.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'MRI Shoulder', 'code' => 'MRISHLD', 'category' => 'radiology', 'default_price' => 7000.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            
            // Dental specific
            ['name' => 'IOPA (Intraoral Periapical)', 'code' => 'IOPA', 'category' => 'dental_radiology', 'default_price' => 150.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'OPG (Orthopantomogram)', 'code' => 'OPG', 'category' => 'dental_radiology', 'default_price' => 500.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Lateral Cephalogram', 'code' => 'LATCEPH', 'category' => 'dental_radiology', 'default_price' => 600.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'CBCT (Full Mouth)', 'code' => 'CBCT', 'category' => 'dental_radiology', 'default_price' => 3000.00, 'sac_code' => '999331', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('lab_test_catalog')->insert($labTests);

        Log::info('VendorSeeder: Created vendors and lab tests', [
            'vendors' => count($vendorLabs),
            'tests' => count($labTests),
        ]);

        $this->command->info('VendorSeeder: created ' . count($vendorLabs) . ' vendor labs and ' . count($labTests) . ' test catalog entries.');
    }
}
