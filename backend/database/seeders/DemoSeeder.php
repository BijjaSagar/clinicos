<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\User;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Visit;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('Starting DemoSeeder');

        // Create demo clinic (matching the actual schema)
        $clinic = Clinic::create([
            'name' => 'Sharma Skin Clinic',
            'slug' => 'sharma-skin-clinic-' . Str::random(6),
            'plan' => 'small',
            'specialties' => ['dermatology'],
            'address_line1' => '123 MG Road',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'phone' => '+912025534567',
            'email' => 'info@sharmaskin.com',
            'gstin' => '27AADCS1234B1ZP',
            'is_active' => true,
            'settings' => [
                'default_gst_rate' => 18,
                'payment_terms' => 'Payment due within 7 days',
                'invoice_prefix' => 'SSC',
            ],
            'trial_ends_at' => now()->addDays(30),
        ]);

        Log::info('Created clinic', ['id' => $clinic->id]);

        // Create demo doctor/owner
        $user = User::create([
            'clinic_id' => $clinic->id,
            'name' => 'Dr. Priya Sharma',
            'email' => 'demo@clinicos.com',
            'phone' => '+919876543210',
            'password' => Hash::make('password'),
            'role' => 'owner',  // Valid: owner, doctor, receptionist, nurse, staff, vendor_admin
            'is_active' => true,
        ]);

        Log::info('Created user', ['id' => $user->id]);

        $clinic->update(['owner_user_id' => $user->id]);

        // Create sample patients
        $patients = [
            ['name' => 'Rahul Mehta', 'phone' => '+919987654321', 'gender' => 'M', 'date_of_birth' => '1985-03-15'],
            ['name' => 'Anita Desai', 'phone' => '+919876512345', 'gender' => 'F', 'date_of_birth' => '1990-07-22'],
            ['name' => 'Vikram Patel', 'phone' => '+919765432109', 'gender' => 'M', 'date_of_birth' => '1978-11-08'],
            ['name' => 'Priyanka Singh', 'phone' => '+919654321098', 'gender' => 'F', 'date_of_birth' => '1995-02-14'],
            ['name' => 'Amit Kumar', 'phone' => '+919543210987', 'gender' => 'M', 'date_of_birth' => '1982-09-30'],
        ];

        foreach ($patients as $index => $patientData) {
            $patient = Patient::create(array_merge($patientData, [
                'clinic_id' => $clinic->id,
                'patient_uid' => 'SSC-' . strtoupper(Str::random(8)),
            ]));

            Log::info('Created patient', ['id' => $patient->id, 'name' => $patient->name]);

            // Create sample appointment for today
            Appointment::create([
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'doctor_user_id' => $user->id,
                'start_time' => now()->setTime(9 + $index, 0),
                'end_time' => now()->setTime(9 + $index, 30),
                'status' => ['scheduled', 'confirmed', 'checked_in'][rand(0, 2)],
                'notes' => 'Regular consultation',
            ]);

            // Create sample visit
            $visit = Visit::create([
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'doctor_user_id' => $user->id,
                'visit_date' => now()->subDays(rand(1, 30)),
                'chief_complaint' => ['Acne', 'Psoriasis', 'Eczema', 'Hair Loss', 'Skin Rash'][$index],
                'status' => 'completed',
            ]);

            // Create sample invoice
            $subtotal = rand(1000, 3000);
            $cgst = round($subtotal * 0.09, 2);
            $sgst = round($subtotal * 0.09, 2);
            $total = $subtotal + $cgst + $sgst;
            $status = ['paid', 'pending', 'partial'][rand(0, 2)];
            $balanceDue = $status === 'paid' ? 0 : ($status === 'partial' ? round($total / 2) : $total);

            $invoice = Invoice::create([
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'invoice_number' => 'SSC-' . now()->format('Y') . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'subtotal' => $subtotal,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'total_amount' => $total,
                'balance_due' => $balanceDue,
                'status' => $status,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Dermatology Consultation',
                'sac_code' => '998314',
                'quantity' => 1,
                'unit_price' => $subtotal,
                'total_price' => $subtotal,
            ]);
        }

        Log::info('DemoSeeder completed successfully');

        $this->command->info('');
        $this->command->info('✅ Demo data seeded successfully!');
        $this->command->info('');
        $this->command->info('📧 Login: demo@clinicos.com');
        $this->command->info('🔐 Password: password');
        $this->command->info('');
    }
}
