<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\RazorpayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReconcileRazorpayPayments extends Command
{
    protected $signature = 'razorpay:reconcile';

    protected $description = 'Reconcile Razorpay payments with local invoice records';

    public function handle(RazorpayService $razorpayService): int
    {
        $this->info('Starting Razorpay reconciliation at ' . now()->toDateTimeString());
        Log::info('razorpay:reconcile started');

        $reconciled = 0;
        $errors     = 0;

        // ── 1. Invoices with a razorpay_payment_id but no paid_at ───────────
        $reconciled += $this->reconcileByPaymentId($razorpayService, $errors);

        // ── 2. Invoices with a razorpay_order_id but no linked payment ───────
        $reconciled += $this->reconcileByOrderId($errors);

        $this->info("Done. Reconciled: {$reconciled}, Errors: {$errors}");
        Log::info('razorpay:reconcile completed', ['reconciled' => $reconciled, 'errors' => $errors]);

        return self::SUCCESS;
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function reconcileByPaymentId(RazorpayService $razorpayService, int &$errors): int
    {
        $invoices = Invoice::whereIn('status', ['finalized', 'partially_paid'])
            ->whereNotNull('razorpay_payment_id')
            ->whereNull('paid_at')
            ->get();

        $this->info("Payment-ID reconciliation: {$invoices->count()} invoices found");
        Log::info('razorpay:reconcile payment-id batch', ['count' => $invoices->count()]);

        $reconciled = 0;

        foreach ($invoices as $invoice) {
            try {
                $result = $razorpayService->fetchPayment($invoice->razorpay_payment_id);

                if (!($result['success'] ?? false)) {
                    Log::warning('razorpay:reconcile fetchPayment failed', [
                        'invoice_id'         => $invoice->id,
                        'razorpay_payment_id' => $invoice->razorpay_payment_id,
                        'error'              => $result['error'] ?? 'unknown',
                    ]);
                    continue;
                }

                $payment = $result['payment'];

                if (($payment['status'] ?? '') !== 'captured') {
                    Log::info('razorpay:reconcile payment not captured, skipping', [
                        'invoice_id'         => $invoice->id,
                        'razorpay_payment_id' => $invoice->razorpay_payment_id,
                        'payment_status'     => $payment['status'] ?? 'unknown',
                    ]);
                    continue;
                }

                $amountPaid = ($payment['amount'] ?? 0) / 100;

                $invoice->update([
                    'paid_at'     => now(),
                    'status'      => 'paid',
                    'amount_paid' => $amountPaid,
                ]);

                $reconciled++;

                Log::info('razorpay:reconcile marked invoice paid (payment_id)', [
                    'invoice_id'         => $invoice->id,
                    'razorpay_payment_id' => $invoice->razorpay_payment_id,
                    'amount_paid'        => $amountPaid,
                ]);

                $this->line("  -> Invoice #{$invoice->id} marked paid (payment {$invoice->razorpay_payment_id}, ₹{$amountPaid})");
            } catch (\Throwable $e) {
                $errors++;
                Log::error('razorpay:reconcile payment-id exception', [
                    'invoice_id' => $invoice->id,
                    'error'      => $e->getMessage(),
                ]);
                $this->error("  -> Failed for invoice #{$invoice->id}: {$e->getMessage()}");
            }
        }

        return $reconciled;
    }

    private function reconcileByOrderId(int &$errors): int
    {
        $key    = config('services.razorpay.key_id');
        $secret = config('services.razorpay.key_secret');

        if (empty($key) || empty($secret)) {
            Log::warning('razorpay:reconcile order-id pass skipped — Razorpay not configured');
            $this->warn('Razorpay not configured, skipping order-id reconciliation.');
            return 0;
        }

        $invoices = Invoice::whereIn('status', ['finalized', 'partially_paid'])
            ->whereNotNull('razorpay_order_id')
            ->whereNull('razorpay_payment_id')
            ->whereNull('paid_at')
            ->get();

        $this->info("Order-ID reconciliation: {$invoices->count()} invoices found");
        Log::info('razorpay:reconcile order-id batch', ['count' => $invoices->count()]);

        $reconciled = 0;

        foreach ($invoices as $invoice) {
            try {
                $response = Http::withBasicAuth($key, $secret)
                    ->get("https://api.razorpay.com/v1/orders/{$invoice->razorpay_order_id}/payments");

                if (!$response->successful()) {
                    Log::warning('razorpay:reconcile order payments fetch failed', [
                        'invoice_id'        => $invoice->id,
                        'razorpay_order_id' => $invoice->razorpay_order_id,
                        'http_status'       => $response->status(),
                    ]);
                    continue;
                }

                $items = $response->json('items') ?? [];

                $capturedPayment = null;
                foreach ($items as $item) {
                    if (($item['status'] ?? '') === 'captured') {
                        $capturedPayment = $item;
                        break;
                    }
                }

                if ($capturedPayment === null) {
                    Log::info('razorpay:reconcile no captured payment for order', [
                        'invoice_id'        => $invoice->id,
                        'razorpay_order_id' => $invoice->razorpay_order_id,
                        'payment_count'     => count($items),
                    ]);
                    continue;
                }

                $amountPaid = ($capturedPayment['amount'] ?? 0) / 100;

                $invoice->update([
                    'paid_at'              => now(),
                    'status'               => 'paid',
                    'amount_paid'          => $amountPaid,
                    'razorpay_payment_id'  => $capturedPayment['id'],
                ]);

                $reconciled++;

                Log::info('razorpay:reconcile marked invoice paid (order_id)', [
                    'invoice_id'         => $invoice->id,
                    'razorpay_order_id'  => $invoice->razorpay_order_id,
                    'razorpay_payment_id' => $capturedPayment['id'],
                    'amount_paid'        => $amountPaid,
                ]);

                $this->line("  -> Invoice #{$invoice->id} marked paid via order {$invoice->razorpay_order_id} (₹{$amountPaid})");
            } catch (\Throwable $e) {
                $errors++;
                Log::error('razorpay:reconcile order-id exception', [
                    'invoice_id' => $invoice->id,
                    'error'      => $e->getMessage(),
                ]);
                $this->error("  -> Failed for invoice #{$invoice->id}: {$e->getMessage()}");
            }
        }

        return $reconciled;
    }
}
