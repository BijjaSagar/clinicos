<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GstSacCodeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $codes = [
            [
                'sac_code'    => '999311',
                'description' => 'Outpatient medical consultation services',
                'gst_rate'    => 0.00,
                'cgst_rate'   => 0.00,
                'sgst_rate'   => 0.00,
                'igst_rate'   => 0.00,
                'is_exempt'   => true,
                'notes'       => 'General OPD consultation — GST exempt under Notification 12/2017-CT(Rate)',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'sac_code'    => '999312',
                'description' => 'Cosmetic and aesthetic medical procedures',
                'gst_rate'    => 18.00,
                'cgst_rate'   => 9.00,
                'sgst_rate'   => 9.00,
                'igst_rate'   => 18.00,
                'is_exempt'   => false,
                'notes'       => 'Cosmetic/aesthetic procedures e.g. Botox, fillers, chemical peels (elective). CGST 9% + SGST 9%.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'sac_code'    => '999321',
                'description' => 'Optician services',
                'gst_rate'    => 5.00,
                'cgst_rate'   => 2.50,
                'sgst_rate'   => 2.50,
                'igst_rate'   => 5.00,
                'is_exempt'   => false,
                'notes'       => 'Supply of spectacles / contact lenses with fitting services.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'sac_code'    => '999322',
                'description' => 'Dental services',
                'gst_rate'    => 0.00,
                'cgst_rate'   => 0.00,
                'sgst_rate'   => 0.00,
                'igst_rate'   => 0.00,
                'is_exempt'   => true,
                'notes'       => 'Basic dental services (extraction, fillings, RCT) — exempt. Cosmetic dental (whitening, veneers) taxed at 18%.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'sac_code'    => '998111',
                'description' => 'Physiotherapy and rehabilitation services',
                'gst_rate'    => 12.00,
                'cgst_rate'   => 6.00,
                'sgst_rate'   => 6.00,
                'igst_rate'   => 12.00,
                'is_exempt'   => false,
                'notes'       => 'Physiotherapy, occupational therapy, speech therapy — 12% GST.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'sac_code'    => '999331',
                'description' => 'Diagnostic and laboratory testing services',
                'gst_rate'    => 5.00,
                'cgst_rate'   => 2.50,
                'sgst_rate'   => 2.50,
                'igst_rate'   => 5.00,
                'is_exempt'   => false,
                'notes'       => 'Pathology, radiology, imaging, clinical lab tests — 5% GST.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        foreach ($codes as $code) {
            DB::table('gst_sac_codes')->updateOrInsert(
                ['sac_code' => $code['sac_code']],
                $code
            );
        }

        $this->command->info('GstSacCodeSeeder: seeded ' . count($codes) . ' SAC codes.');
    }
}
