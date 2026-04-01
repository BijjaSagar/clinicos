<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\RazorpayWebhookEvent;
use App\Services\RazorpayService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentWebController extends Controller
{
    private RazorpayService $razorpay;
    private ?WhatsAppService $whatsAppService;

    public function __construct(RazorpayService $razorpay, ?WhatsAppService $whatsAppService = null)
    {
        $this->razorpay = $razorpay;
        $this->whatsAppService = $whatsAppService;
    }
    public function index(Request $request)
    {
        Log::info('PaymentWebController@index', ['user' => auth()->id()]);

        try {
            $clinicId = auth()->user()->clinic_id;
            
            $query = Payment::with(['patient', 'invoice', 'recordedBy'])
                ->whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                ->orderByDesc('payment_date');

            // Filter by payment method
            if ($request->filled('method')) {
                $query->where('payment_method', $request->method);
            }

            // Filter by date range
            if ($request->filled('from')) {
                $query->whereDate('payment_date', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('payment_date', '<=', $request->to);
            }

            $payments = $query->paginate(20);

            // Stats
            $today = now()->toDateString();
            $weekStart = now()->startOfWeek()->toDateString();
            $monthStart = now()->startOfMonth()->toDateString();

            $stats = [
                'today' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('payment_date', $today)
                    ->sum('amount') ?? 0,
                'today_count' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('payment_date', $today)
                    ->count(),
                'week' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('payment_date', '>=', $weekStart)
                    ->sum('amount') ?? 0,
                'week_count' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('payment_date', '>=', $weekStart)
                    ->count(),
                'month' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('payment_date', '>=', $monthStart)
                    ->sum('amount') ?? 0,
                'month_count' => Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('payment_date', '>=', $monthStart)
                    ->count(),
                'pending' => Invoice::where('clinic_id', $clinicId)
                    ->whereIn('payment_status', ['pending', 'partial'])
                    ->selectRaw('SUM(total - paid) as pending_total')
                    ->value('pending_total') ?? 0,
                'pending_count' => Invoice::where('clinic_id', $clinicId)
                    ->whereIn('payment_status', ['pending', 'partial'])
                    ->count(),
            ];

            // Payment method breakdown for this month
            $methodBreakdown = Payment::whereHas('invoice', fn($q) => $q->where('clinic_id', $clinicId))
                ->whereDate('payment_date', '>=', $monthStart)
                ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('payment_method')
                ->get()
                ->keyBy('payment_method');

            Log::info('PaymentWebController@index success', ['payments_count' => $payments->count()]);

            $razorpayConfigured = $this->razorpay->isConfigured();

            return view('payments.index', compact('payments', 'stats', 'methodBreakdown', 'razorpayConfigured'));
            
        } catch (\Throwable $e) {
            Log::error('PaymentWebController@index error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            return view('payments.index', [
                'payments' => collect(),
                'stats' => [
                    'today' => 0, 'today_count' => 0,
                    'week' => 0, 'week_count' => 0,
                    'month' => 0, 'month_count' => 0,
                    'pending' => 0, 'pending_count' => 0,
                ],
                'methodBreakdown' => collect(),
                'razorpayConfigured' => false,
                'error' => 'Could not load payments: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create Razorpay order for an invoice
     */
    public function createOrder(Request $request, Invoice $invoice): JsonResponse
    {
        Log::info('PaymentWebController: Creating Razorpay order', ['invoice_id' => $invoice->id]);

        abort_unless(auth()->user()->clinic_id === $invoice->clinic_id, 403);

        $pendingAmount = max(0, (float) $invoice->total - (float) $invoice->paid);
        Log::info('PaymentWebController: createOrder pending amount', [
            'invoice_id' => $invoice->id,
            'pending' => $pendingAmount,
        ]);

        $result = $this->razorpay->createOrder([
            'amount' => $pendingAmount,
            'receipt' => 'inv_' . $invoice->invoice_number,
            'notes' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'clinic_id' => $invoice->clinic_id,
                'patient_id' => $invoice->patient_id,
            ],
        ]);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'order_id' => $result['order_id'],
                'amount' => $result['amount'],
                'currency' => $result['currency'],
                'key_id' => $this->razorpay->getKeyId(),
                'invoice_number' => $invoice->invoice_number,
            ]);
        }

        return response()->json(['success' => false, 'error' => $result['error']], 500);
    }

    /**
     * Create payment link for an invoice
     */
    public function createPaymentLink(Request $request, Invoice $invoice): JsonResponse
    {
        Log::info('PaymentWebController: Creating payment link', ['invoice_id' => $invoice->id]);

        abort_unless(auth()->user()->clinic_id === $invoice->clinic_id, 403);

        $patient = Patient::find($invoice->patient_id);
        $clinic = auth()->user()->clinic;

        $result = $this->razorpay->createInvoicePaymentLink($invoice, $patient, $clinic);

        if ($result['success']) {
            $invoice->update(['payment_link' => $result['short_url']]);

            Log::info('PaymentWebController: Payment link generated', [
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'short_url' => $result['short_url'],
                'has_whatsapp_service' => !empty($this->whatsAppService),
            ]);

            if ($this->whatsAppService && $patient && !empty($patient->phone)) {
                try {
                    $waResponse = $this->whatsAppService->sendPaymentReminder($patient, $invoice, $result['short_url']);
                    Log::info('PaymentWebController: Payment link WhatsApp reminder sent', [
                        'invoice_id' => $invoice->id,
                        'patient_id' => $patient->id,
                        'wa_success' => $waResponse['success'] ?? null,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('PaymentWebController: Payment link WhatsApp send failed', [
                        'invoice_id' => $invoice->id,
                        'patient_id' => $patient?->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::warning('PaymentWebController: Payment link WhatsApp skipped', [
                    'invoice_id' => $invoice->id,
                    'patient_exists' => !empty($patient),
                    'patient_has_phone' => !empty($patient?->phone),
                    'has_whatsapp_service' => !empty($this->whatsAppService),
                ]);
            }

            return response()->json([
                'success' => true,
                'link_id' => $result['link_id'],
                'short_url' => $result['short_url'],
            ]);
        }

        return response()->json(['success' => false, 'error' => $result['error']], 500);
    }

    /**
     * Create QR code for payment
     */
    public function createQRCode(Request $request, Invoice $invoice): JsonResponse
    {
        Log::info('PaymentWebController: Creating QR code', ['invoice_id' => $invoice->id]);

        abort_unless(auth()->user()->clinic_id === $invoice->clinic_id, 403);

        $pendingAmount = max(0, (float) $invoice->total - (float) $invoice->paid);
        Log::info('PaymentWebController: createQRCode pending amount', [
            'invoice_id' => $invoice->id,
            'pending' => $pendingAmount,
        ]);
        $clinic = auth()->user()->clinic;

        $result = $this->razorpay->createQRCode([
            'name' => $clinic->name . ' - ' . $invoice->invoice_number,
            'amount' => $pendingAmount,
            'description' => "Payment for Invoice #{$invoice->invoice_number}",
            'notes' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ],
        ]);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'qr_id' => $result['qr_id'],
                'image_url' => $result['image_url'],
                'amount' => $result['amount'],
            ]);
        }

        return response()->json(['success' => false, 'error' => $result['error']], 500);
    }

    /**
     * Verify payment and update invoice
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        Log::info('PaymentWebController: Verifying payment', ['invoice_id' => $request->input('invoice_id')]);

        $validated = $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'invoice_id' => 'required|exists:invoices,id',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);
        abort_unless(auth()->user()->clinic_id === $invoice->clinic_id, 403);

        if (Payment::where('razorpay_payment_id', $validated['razorpay_payment_id'])->exists()) {
            Log::info('PaymentWebController: verifyPayment idempotent — payment row exists', [
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Payment already recorded',
                'duplicate' => true,
            ]);
        }

        $isValid = $this->razorpay->verifySignature(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature']
        );

        if (!$isValid) {
            Log::warning('PaymentWebController: Invalid signature', ['payment_id' => $validated['razorpay_payment_id']]);
            return response()->json(['success' => false, 'error' => 'Invalid payment signature'], 400);
        }

        $paymentDetails = $this->razorpay->fetchPayment($validated['razorpay_payment_id']);

        if (!$paymentDetails['success']) {
            Log::error('PaymentWebController: fetchPayment failed in verify', [
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
            ]);
            return response()->json(['success' => false, 'error' => 'Could not fetch payment details'], 500);
        }

        $payment = $paymentDetails['payment'];
        $amount = ($payment['amount'] ?? 0) / 100;

        try {
            DB::beginTransaction();

            $invoice->refresh();
            $invoice->recordPayment($amount, 'razorpay', $validated['razorpay_payment_id']);

            Payment::where('razorpay_payment_id', $validated['razorpay_payment_id'])->update([
                'razorpay_order_id' => $validated['razorpay_order_id'],
                'razorpay_signature' => $validated['razorpay_signature'],
                'recorded_by' => auth()->id(),
                'notes' => json_encode([
                    'order_id' => $validated['razorpay_order_id'],
                    'method' => $payment['method'] ?? 'card',
                    'source' => 'verify_payment',
                ]),
            ]);

            $invoice->refresh();

            DB::commit();

            Log::info('PaymentWebController: Payment verified and recorded', [
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_status' => $invoice->payment_status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'amount' => $amount,
                'payment_status' => $invoice->payment_status,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PaymentWebController: Payment recording failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'error' => 'Failed to record payment'], 500);
        }
    }

    /**
     * Initiate refund
     */
    public function initiateRefund(Request $request, Payment $payment): JsonResponse
    {
        Log::info('PaymentWebController: Initiating refund', ['payment_id' => $payment->id]);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:200',
        ]);

        if (!$payment->razorpay_payment_id || !str_starts_with($payment->razorpay_payment_id, 'pay_')) {
            Log::warning('PaymentWebController: refund blocked — no Razorpay payment id', [
                'payment_row_id' => $payment->id,
            ]);
            return response()->json(['success' => false, 'error' => 'This payment cannot be refunded via Razorpay'], 400);
        }

        $result = $this->razorpay->initiateRefund(
            $payment->razorpay_payment_id,
            (int) $validated['amount'],
            ['reason' => $validated['reason'] ?? 'Customer request']
        );

        if ($result['success']) {
            $payment->update([
                'razorpay_refund_id' => $result['refund_id'],
                'refund_amount' => $validated['amount'],
            ]);

            Log::info('PaymentWebController: Refund recorded on payment row', [
                'payment_id' => $payment->id,
                'refund_id' => $result['refund_id'],
            ]);

            return response()->json([
                'success' => true,
                'refund_id' => $result['refund_id'],
                'amount' => $result['amount'],
            ]);
        }

        return response()->json(['success' => false, 'error' => $result['error']], 500);
    }

    /**
     * Handle Razorpay webhook (idempotent; reconciles invoice when notes contain invoice_id)
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $raw = $request->getContent();
        $eventType = $request->input('event');

        Log::info('PaymentWebController: Webhook received', [
            'event' => $eventType,
            'has_event_id' => $request->filled('id'),
            'bytes' => strlen($raw),
        ]);

        $webhookSecret = config('services.razorpay.webhook_secret');

        if ($webhookSecret) {
            $signature = $request->header('X-Razorpay-Signature');
            $expectedSignature = hash_hmac('sha256', $raw, $webhookSecret);

            if (!hash_equals($expectedSignature, $signature ?? '')) {
                Log::warning('PaymentWebController: Invalid webhook signature', [
                    'has_header' => !empty($signature),
                ]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        } else {
            Log::warning('PaymentWebController: webhook_secret not configured — signature not verified');
        }

        $eventId = $request->input('id') ?? ('hash_' . hash('sha256', $raw));

        if (RazorpayWebhookEvent::where('event_id', $eventId)->exists()) {
            Log::info('PaymentWebController: Webhook duplicate event_id — acknowledged', ['event_id' => $eventId]);
            return response()->json(['status' => 'ok', 'duplicate' => true]);
        }

        $row = RazorpayWebhookEvent::create([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payload_json' => $raw,
            'payload_hash' => hash('sha256', $raw),
        ]);

        try {
            if ($eventType === 'payment.captured') {
                $this->reconcilePaymentCapturedFromPayload($request->all(), $row);
            } elseif ($eventType === 'payment_link.paid') {
                $this->reconcilePaymentCapturedFromPayload($request->all(), $row);
            } elseif ($eventType === 'qr_code.credited') {
                Log::info('PaymentWebController: qr_code.credited — no invoice reconciliation without notes', [
                    'event_id' => $eventId,
                ]);
            } else {
                Log::info('PaymentWebController: Webhook event not reconciled', ['event_type' => $eventType]);
            }

            $row->update([
                'processed_at' => now(),
                'processing_note' => 'ok',
            ]);
        } catch (\Throwable $e) {
            Log::error('PaymentWebController: Webhook processing error', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $row->update(['processing_note' => $e->getMessage()]);

            return response()->json(['status' => 'error'], 500);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Apply payment.captured / payment_link.paid when payload includes invoice_id on the payment entity notes.
     */
    private function reconcilePaymentCapturedFromPayload(array $payload, RazorpayWebhookEvent $row): void
    {
        $entity = data_get($payload, 'payload.payment.entity');
        if (!$entity || empty($entity['id'])) {
            Log::warning('PaymentWebController: reconcile — missing payload.payment.entity', []);
            return;
        }

        $payId = $entity['id'];
        $amountPaise = (int) ($entity['amount'] ?? 0);
        $amount = $amountPaise / 100;
        $notes = is_array($entity['notes'] ?? null) ? $entity['notes'] : [];

        $invoiceIdRaw = $notes['invoice_id'] ?? null;
        if ($invoiceIdRaw === null || $invoiceIdRaw === '') {
            Log::info('PaymentWebController: reconcile — no invoice_id in notes', [
                'pay_id' => $payId,
            ]);
            return;
        }

        $invoiceId = (int) $invoiceIdRaw;

        if (Payment::where('razorpay_payment_id', $payId)->exists()) {
            Log::info('PaymentWebController: reconcile — payment already stored', ['pay_id' => $payId]);
            $row->fill(['invoice_id' => $invoiceId, 'razorpay_payment_id' => $payId])->save();

            return;
        }

        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            Log::warning('PaymentWebController: reconcile — invoice missing', ['invoice_id' => $invoiceId]);

            return;
        }

        $invoice->recordPayment($amount, 'razorpay', $payId);
        Payment::where('razorpay_payment_id', $payId)->update([
            'notes' => json_encode([
                'source' => 'razorpay_webhook',
                'order_id' => $entity['order_id'] ?? null,
            ]),
        ]);

        $row->fill(['invoice_id' => $invoiceId, 'razorpay_payment_id' => $payId])->save();

        Log::info('PaymentWebController: reconcile complete', [
            'invoice_id' => $invoiceId,
            'pay_id' => $payId,
            'amount' => $amount,
        ]);
    }
}
