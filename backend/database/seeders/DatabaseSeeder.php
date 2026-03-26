<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeders.
     * Order matters: respect foreign-key dependencies.
     */
    public function run(): void
    {
        $this->call([
            GstSacCodeSeeder::class,         // no FK deps
            ClinicSeeder::class,             // creates clinics
            UserSeeder::class,               // needs clinics
            PatientSeeder::class,            // needs clinics
            AppointmentServiceSeeder::class, // needs clinics
            DoctorAvailabilitySeeder::class, // needs clinics + users
            AppointmentSeeder::class,        // needs clinics + patients + users
            VisitSeeder::class,              // needs appointments + patients + users
            IndianDrugSeeder::class,         // standalone lookup table
            InvoiceSeeder::class,            // needs visits + patients + clinics
            VendorSeeder::class,             // vendor_labs + lab_test_catalog
        ]);
    }
}
