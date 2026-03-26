<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('InvoiceSeeder: Starting seeder');
        $now = Carbon::now();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('invoices')->truncate();
        DB::table('invoice_items')->truncate();
        DB::table('payments')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Get completed visits
        $visits = DB::table('visits')
            ->join('appointments', 'visits.appointment_id', '=', 'appointments.id')
            ->select('visits.*', 'appointments.service_id', 'appointments.scheduled_at')
            ->get();

        if ($visits->isEmpty()) {
            $this->command->error('InvoiceSeeder: No visits found. Run VisitSeeder first.');
            Log::error('InvoiceSeeder: No visits found');
            return;
        }

        // Get SAC codes
        $sacCodes = DB::table('gst_sac_codes')->pluck('gst_rate', 'sac_code')->toArray();

        // Get services for pricing
        $services = DB::table('appointment_services')->get()->keyBy('id');

        $invoices = [];
        $invoiceItems = [];
        $payments = [];
        $invoiceCounter = [];

        foreach ($visits as $visit) {
            $clinicId = $visit->clinic_id;
            
            // Initialize counter for clinic
            if (!isset($invoiceCounter[$clinicId])) {
                $clinicSlug = DB::table('clinics')->where('id', $clinicId)->value('slug');
                $prefix = strtoupper(substr(str_replace('-', '', $clinicSlug), 0, 4));
                $invoiceCounter[$clinicId] = ['prefix' => $prefix, 'count' => 1];
            }

            // Generate invoice number
            $invoiceNumber = $invoiceCounter[$clinicId]['prefix'] . '-' . 
                             date('Ym', strtotime($visit->created_at)) . '-' . 
                             str_pad($invoiceCounter[$clinicId]['count'], 4, '0', STR_PAD_LEFT);
            $invoiceCounter[$clinicId]['count']++;

            // Get service details
            $service = $services[$visit->service_id] ?? null;
            $sacCode = $service ? $service->sac_code : '999311';
            $gstRate = $sacCodes[$sacCode] ?? 0;
            
            $basePrice = $service ? $service->default_price : 500.00;
            $cgstRate = $gstRate / 2;
            $sgstRate = $gstRate / 2;
            $cgstAmount = $basePrice * ($cgstRate / 100);
            $sgstAmount = $basePrice * ($sgstRate / 100);
            $totalAmount = $basePrice + $cgstAmount + $sgstAmount;

            $invoiceDate = Carbon::parse($visit->created_at);

            $invoiceId = count($invoices) + 1;
            
            $invoices[] = [
                'clinic_id' => $clinicId,
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'due_date' => $invoiceDate->copy()->addDays(7),
                'subtotal' => $basePrice,
                'discount_amount' => 0,
                'cgst_total' => $cgstAmount,
                'sgst_total' => $sgstAmount,
                'igst_total' => 0,
                'total_amount' => $totalAmount,
                'amount_paid' => $totalAmount, // All invoices are paid for simplicity
                'status' => 'paid',
                'notes' => null,
                'created_at' => $invoiceDate,
                'updated_at' => $invoiceDate,
            ];

            // Invoice item
            $invoiceItems[] = [
                'invoice_id' => $invoiceId,
                'description' => $service ? $service->name : 'Consultation',
                'sac_code' => $sacCode,
                'quantity' => 1,
                'unit_price' => $basePrice,
                'discount_percent' => 0,
                'cgst_rate' => $cgstRate,
                'sgst_rate' => $sgstRate,
                'igst_rate' => 0,
                'line_total' => $totalAmount,
                'created_at' => $invoiceDate,
                'updated_at' => $invoiceDate,
            ];

            // Payment record
            $paymentMethod = ['upi', 'card', 'cash'][rand(0, 2)];
            $payments[] = [
                'invoice_id' => $invoiceId,
                'amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'payment_date' => $invoiceDate,
                'razorpay_payment_id' => $paymentMethod !== 'cash' ? 'pay_' . strtoupper(substr(md5($invoiceNumber), 0, 14)) : null,
                'razorpay_order_id' => $paymentMethod !== 'cash' ? 'order_' . strtoupper(substr(md5($invoiceNumber . 'order'), 0, 14)) : null,
                'status' => 'captured',
                'notes' => null,
                'created_at' => $invoiceDate,
                'updated_at' => $invoiceDate,
            ];
        }

        // Add a few unpaid invoices for today
        $clinic1 = DB::table('clinics')->where('slug', 'sharma-skin-hair')->value('id');
        $patient1 = DB::table('patients')->where('clinic_id', $clinic1)->first();
        
        if ($patient1) {
            $unpaidInvoiceId = count($invoices) + 1;
            $unpaidInvoiceNumber = $invoiceCounter[$clinic1]['prefix'] . '-' . 
                                   date('Ym') . '-' . 
                                   str_pad($invoiceCounter[$clinic1]['count'], 4, '0', STR_PAD_LEFT);
            
            $invoices[] = [
                'clinic_id' => $clinic1,
                'patient_id' => $patient1->id,
                'visit_id' => null,
                'invoice_number' => $unpaidInvoiceNumber,
                'invoice_date' => $now,
                'due_date' => $now->copy()->addDays(7),
                'subtotal' => 3500.00,
                'discount_amount' => 0,
                'cgst_total' => 315.00, // 9%
                'sgst_total' => 315.00, // 9%
                'igst_total' => 0,
                'total_amount' => 4130.00,
                'amount_paid' => 0,
                'status' => 'pending',
                'notes' => 'Chemical peel procedure - Payment pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $invoiceItems[] = [
                'invoice_id' => $unpaidInvoiceId,
                'description' => 'Chemical Peel',
                'sac_code' => '999312',
                'quantity' => 1,
                'unit_price' => 3500.00,
                'discount_percent' => 0,
                'cgst_rate' => 9.00,
                'sgst_rate' => 9.00,
                'igst_rate' => 0,
                'line_total' => 4130.00,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('invoices')->insert($invoices);
        DB::table('invoice_items')->insert($invoiceItems);
        DB::table('payments')->insert($payments);

        Log::info('InvoiceSeeder: Created invoices', [
            'invoices' => count($invoices),
            'items' => count($invoiceItems),
            'payments' => count($payments),
        ]);

        $this->command->info('InvoiceSeeder: created ' . count($invoices) . ' invoices with ' . count($invoiceItems) . ' items and ' . count($payments) . ' payments.');
    }
}
