<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoInvoiceService
{
    /**
     * Auto-create a consultation invoice when a visit is finalised.
     * Skips if the visit already has an invoice.
     */
    public function fromVisit(int $visitId, int $patientId, int $clinicId, int $doctorId): ?Invoice
    {
        // Don't duplicate
        $exists = Invoice::where('visit_id', $visitId)->exists();
        if ($exists) return null;

        try {
            // Get doctor's consultation fee (fallback to clinic default, then 500)
            $fee = DB::table('users')->where('id', $doctorId)->value('consultation_fee') ?? 0;
            if ($fee <= 0) {
                $fee = DB::table('clinics')->where('id', $clinicId)->value('default_consultation_fee') ?? 500;
            }
            $fee = (float) $fee;

            // Get GST rate
            $clinic   = DB::table('clinics')->where('id', $clinicId)->first();
            $gstRate  = ($clinic->settings ?? null) ? (json_decode($clinic->settings, true)['default_gst_rate'] ?? 0) : 0;
            $gstRate  = (float) $gstRate;
            $cgstRate = $gstRate / 2;
            $sgstRate = $gstRate / 2;
            $cgst     = round($fee * $cgstRate / 100, 2);
            $sgst     = round($fee * $sgstRate / 100, 2);
            $total    = round($fee + $cgst + $sgst, 2);

            return DB::transaction(function () use ($visitId, $patientId, $clinicId, $doctorId, $fee, $cgst, $sgst, $total, $gstRate) {
                $invoice = Invoice::create([
                    'clinic_id'      => $clinicId,
                    'patient_id'     => $patientId,
                    'visit_id'       => $visitId,
                    'invoice_date'   => now(),
                    'subtotal'       => $fee,
                    'discount_amount'=> 0,
                    'cgst_amount'    => $cgst,
                    'sgst_amount'    => $sgst,
                    'total'          => $total,
                    'paid'           => 0,
                    'payment_status' => 'pending',
                    'notes'          => 'Auto-generated on visit finalisation',
                ]);

                InvoiceItem::create([
                    'invoice_id'     => $invoice->id,
                    'description'    => 'Consultation Fee',
                    'item_type'      => 'consultation',
                    'sac_code'       => '999312',
                    'gst_rate'       => $gstRate,
                    'unit_price'     => $fee,
                    'quantity'       => 1,
                    'taxable_amount' => $fee,
                    'cgst_amount'    => $cgst,
                    'sgst_amount'    => $sgst,
                    'total'          => $fee + $cgst + $sgst,
                    'sort_order'     => 0,
                ]);

                Log::info('AutoInvoiceService: visit invoice created', ['invoice_id' => $invoice->id, 'visit_id' => $visitId]);
                return $invoice;
            });
        } catch (\Throwable $e) {
            Log::error('AutoInvoiceService::fromVisit failed', ['error' => $e->getMessage(), 'visit_id' => $visitId]);
            return null;
        }
    }

    /**
     * Auto-create an IPD discharge invoice with room charges.
     * Skips if an invoice already exists for this admission.
     */
    public function fromIpdDischarge(int $admissionId, int $patientId, int $clinicId): ?Invoice
    {
        $exists = Invoice::where('clinic_id', $clinicId)
            ->where('patient_id', $patientId)
            ->where('notes', 'like', "%admission #{$admissionId}%")
            ->exists();
        if ($exists) return null;

        try {
            $admission = DB::table('ipd_admissions')->where('id', $admissionId)->first();
            $bed       = $admission ? DB::table('beds')->where('id', $admission->bed_id)->first() : null;
            $ward      = $bed ? DB::table('wards')->where('id', $bed->ward_id)->first() : null;

            $admissionDate  = $admission?->admission_date  ? \Carbon\Carbon::parse($admission->admission_date)  : now()->subDay();
            $dischargeDate  = $admission?->discharge_date  ? \Carbon\Carbon::parse($admission->discharge_date)  : now();
            $days           = max(1, $admissionDate->diffInDays($dischargeDate) + 1);

            $dailyRate   = (float) ($ward?->daily_rate ?? 0);
            if ($dailyRate <= 0) $dailyRate = 1000; // sensible default if not configured

            $roomCharge  = round($days * $dailyRate, 2);

            $clinic      = DB::table('clinics')->where('id', $clinicId)->first();
            $gstRate     = ($clinic->settings ?? null) ? (json_decode($clinic->settings, true)['default_gst_rate'] ?? 0) : 0;
            $gstRate     = (float) $gstRate;
            $cgst        = round($roomCharge * $gstRate / 2 / 100, 2);
            $sgst        = round($roomCharge * $gstRate / 2 / 100, 2);
            $total       = round($roomCharge + $cgst + $sgst, 2);

            return DB::transaction(function () use ($admissionId, $patientId, $clinicId, $roomCharge, $cgst, $sgst, $total, $gstRate, $days, $dailyRate, $ward) {
                $invoice = Invoice::create([
                    'clinic_id'      => $clinicId,
                    'patient_id'     => $patientId,
                    'invoice_date'   => now(),
                    'subtotal'       => $roomCharge,
                    'discount_amount'=> 0,
                    'cgst_amount'    => $cgst,
                    'sgst_amount'    => $sgst,
                    'total'          => $total,
                    'paid'           => 0,
                    'payment_status' => 'pending',
                    'notes'          => "Auto-generated on discharge · admission #{$admissionId}",
                ]);

                InvoiceItem::create([
                    'invoice_id'     => $invoice->id,
                    'description'    => "Room charges · {$days} day(s) × ₹{$dailyRate}/day" . ($ward ? " ({$ward->name})" : ''),
                    'item_type'      => 'room_charge',
                    'sac_code'       => '999311',
                    'gst_rate'       => $gstRate,
                    'unit_price'     => $dailyRate,
                    'quantity'       => $days,
                    'taxable_amount' => $roomCharge,
                    'cgst_amount'    => $cgst,
                    'sgst_amount'    => $sgst,
                    'total'          => $roomCharge + $cgst + $sgst,
                    'sort_order'     => 0,
                ]);

                Log::info('AutoInvoiceService: IPD discharge invoice created', ['invoice_id' => $invoice->id, 'admission_id' => $admissionId, 'days' => $days]);
                return $invoice;
            });
        } catch (\Throwable $e) {
            Log::error('AutoInvoiceService::fromIpdDischarge failed', ['error' => $e->getMessage(), 'admission_id' => $admissionId]);
            return null;
        }
    }

    /**
     * Auto-create a pharmacy invoice from a dispensing record.
     */
    public function fromPharmacyDispensing(int $dispensingId, int $clinicId, ?int $patientId): ?Invoice
    {
        if (!$patientId) return null;  // Can't create invoice without patient

        try {
            $dispensing = DB::table('pharmacy_dispensings')->where('id', $dispensingId)->first();
            if (!$dispensing) return null;

            $total = (float) ($dispensing->total_amount ?? 0);
            if ($total <= 0) return null;

            $discount = (float) ($dispensing->discount_amount ?? 0);
            $subtotal = round($total + $discount, 2);

            return DB::transaction(function () use ($dispensingId, $clinicId, $patientId, $dispensing, $total, $subtotal) {
                $invoice = Invoice::create([
                    'clinic_id'      => $clinicId,
                    'patient_id'     => $patientId,
                    'invoice_date'   => now(),
                    'subtotal'       => $subtotal,
                    'discount_amount'=> (float)($dispensing->discount_amount ?? 0),
                    'cgst_amount'    => 0,
                    'sgst_amount'    => 0,
                    'total'          => $total,
                    'paid'           => in_array($dispensing->payment_mode, ['cash','card','upi']) ? $total : 0,
                    'payment_status' => in_array($dispensing->payment_mode, ['cash','card','upi']) ? 'paid' : 'pending',
                    'notes'          => "Auto-generated · pharmacy dispensing #{$dispensing->dispensing_number}",
                ]);

                InvoiceItem::create([
                    'invoice_id'     => $invoice->id,
                    'description'    => "Pharmacy — {$dispensing->dispensing_number}",
                    'item_type'      => 'pharmacy',
                    'sac_code'       => '999313',
                    'gst_rate'       => 0,
                    'unit_price'     => $subtotal,
                    'quantity'       => 1,
                    'taxable_amount' => $subtotal,
                    'cgst_amount'    => 0,
                    'sgst_amount'    => 0,
                    'total'          => $total,
                    'sort_order'     => 0,
                ]);

                Log::info('AutoInvoiceService: pharmacy invoice created', ['invoice_id' => $invoice->id, 'dispensing_id' => $dispensingId]);
                return $invoice;
            });
        } catch (\Throwable $e) {
            Log::error('AutoInvoiceService::fromPharmacyDispensing failed', ['error' => $e->getMessage(), 'dispensing_id' => $dispensingId]);
            return null;
        }
    }
}
