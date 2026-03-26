<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api as RazorpayApi;

class PaymentController extends Controller
{
    /**
     * List payments
     */
    public function index(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching payments', ['clinic_id' => $clinicId]);

        $query = Payment::forClinic($clinicId)
            ->with(['invoice', 'patient']);

        if ($request->invoice_id) {
            $query->where('invoice_id', $request->invoice_id);
        }

        if ($request->method) {
            $query->byMethod($request->method);
        }

        if ($request->from_date) {
            $query->where('payment_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->where('payment_date', '<=', $request->to_date);
        }

        $payments = $query->orderBy('payment_date', 'desc')->paginate(20);

        Log::info('Payments retrieved', ['count' => $payments->total()]);

        return response()->json($payments);
    }

    /**
     * Create Razorpay order
     */
    public function createRazorpayOrder(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Creating Razorpay order', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $invoice = Invoice::forClinic($clinicId)->findOrFail($validated['invoice_id']);

        // Verify amount doesn't exceed balance
        $balanceDue = $invoice->getBalanceDue();
        if ($validated['amount'] > $balanceDue) {
            Log::warning('Payment amount exceeds balance', [
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
                'balance_due' => $balanceDue
            ]);
            return response()->json([
                'message' => 'Amount exceeds balance due',
                'balance_due' => $balanceDue,
            ], 400);
        }

        try {
            // Initialize Razorpay
            $razorpay = new RazorpayApi(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );

            $orderData = [
                'receipt' => $invoice->invoice_number,
                'amount' => (int)($validated['amount'] * 100), // in paise
                'currency' => 'INR',
                'notes' => [
                    'invoice_id' => $invoice->id,
                    'clinic_id' => $clinicId,
                    'patient_id' => $invoice->patient_id,
                ],
            ];

            $order = $razorpay->order->create($orderData);

            Log::info('Razorpay order created', [
                'order_id' => $order->id,
                'invoice_id' => $invoice->id
            ]);

            return response()->json([
                'order_id' => $order->id,
                'amount' => $validated['amount'],
                'currency' => 'INR',
                'key' => config('services.razorpay.key'),
                'invoice_number' => $invoice->invoice_number,
            ]);

        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to create payment order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify Razorpay payment
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Verifying Razorpay payment', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);

        $invoice = Invoice::forClinic($clinicId)->findOrFail($validated['invoice_id']);

        // Verify signature
        $expectedSignature = hash_hmac(
            'sha256',
            $validated['razorpay_order_id'] . '|' . $validated['razorpay_payment_id'],
            config('services.razorpay.secret')
        );

        if (!hash_equals($expectedSignature, $validated['razorpay_signature'])) {
            Log::warning('Razorpay signature verification failed', [
                'invoice_id' => $invoice->id,
                'order_id' => $validated['razorpay_order_id']
            ]);
            return response()->json([
                'message' => 'Payment verification failed',
            ], 400);
        }

        // Record payment
        $payment = $invoice->recordPayment(
            $validated['amount'],
            Payment::METHOD_UPI, // Default to UPI, could be card
            $validated['razorpay_payment_id']
        );

        // Update payment with Razorpay details
        $payment->update([
            'razorpay_order_id' => $validated['razorpay_order_id'],
            'razorpay_signature' => $validated['razorpay_signature'],
        ]);

        Log::info('Razorpay payment verified and recorded', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => $validated['amount']
        ]);

        return response()->json([
            'message' => 'Payment successful',
            'payment' => $payment,
            'invoice' => $invoice->fresh(),
        ]);
    }

    /**
     * Record cash/manual payment
     */
    public function recordPayment(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Recording manual payment', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,upi,card,netbanking,wallet',
            'transaction_ref' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:300',
        ]);

        $invoice = Invoice::forClinic($clinicId)->findOrFail($validated['invoice_id']);

        $balanceDue = $invoice->getBalanceDue();
        if ($validated['amount'] > $balanceDue) {
            return response()->json([
                'message' => 'Amount exceeds balance due',
                'balance_due' => $balanceDue,
            ], 400);
        }

        $payment = $invoice->recordPayment($validated['amount'], $validated['payment_method']);

        if (isset($validated['transaction_ref'])) {
            $payment->update([
                'transaction_ref' => $validated['transaction_ref'],
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => $request->user()->id,
            ]);
        }

        Log::info('Manual payment recorded', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => $validated['amount'],
            'method' => $validated['payment_method']
        ]);

        return response()->json([
            'message' => 'Payment recorded successfully',
            'payment' => $payment,
            'invoice' => $invoice->fresh(),
        ]);
    }

    /**
     * Get outstanding invoices
     */
    public function outstanding(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching outstanding invoices', ['clinic_id' => $clinicId]);

        $invoices = Invoice::forClinic($clinicId)
            ->outstanding()
            ->with('patient')
            ->orderBy('invoice_date', 'asc')
            ->get();

        $summary = [
            'total_outstanding' => $invoices->sum(fn($i) => $i->getBalanceDue()),
            'count' => $invoices->count(),
            'overdue_count' => $invoices->filter(fn($i) => $i->isOverdue())->count(),
            'overdue_amount' => $invoices->filter(fn($i) => $i->isOverdue())->sum(fn($i) => $i->getBalanceDue()),
        ];

        // Group by aging
        $aging = [
            '0-30' => ['count' => 0, 'amount' => 0],
            '31-60' => ['count' => 0, 'amount' => 0],
            '61-90' => ['count' => 0, 'amount' => 0],
            '90+' => ['count' => 0, 'amount' => 0],
        ];

        foreach ($invoices as $invoice) {
            $daysOverdue = $invoice->invoice_date->diffInDays(now());
            $balance = $invoice->getBalanceDue();

            if ($daysOverdue <= 30) {
                $aging['0-30']['count']++;
                $aging['0-30']['amount'] += $balance;
            } elseif ($daysOverdue <= 60) {
                $aging['31-60']['count']++;
                $aging['31-60']['amount'] += $balance;
            } elseif ($daysOverdue <= 90) {
                $aging['61-90']['count']++;
                $aging['61-90']['amount'] += $balance;
            } else {
                $aging['90+']['count']++;
                $aging['90+']['amount'] += $balance;
            }
        }

        Log::info('Outstanding invoices retrieved', [
            'count' => $summary['count'],
            'total' => $summary['total_outstanding']
        ]);

        return response()->json([
            'summary' => $summary,
            'aging' => $aging,
            'invoices' => $invoices,
        ]);
    }
}
