<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay Payment Service
 * Handles payment collection, QR codes, payment links, and webhook processing
 */
class RazorpayService
{
    private ?string $keyId;
    private ?string $keySecret;
    private string $baseUrl = 'https://api.razorpay.com/v1';

    public function __construct()
    {
        $this->keyId = config('services.razorpay.key_id');
        $this->keySecret = config('services.razorpay.secret');
    }

    /**
     * Check if Razorpay is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->keyId) && !empty($this->keySecret);
    }

    /**
     * Create a payment order
     */
    public function createOrder(array $params): array
    {
        Log::info('RazorpayService: Creating order', ['amount' => $params['amount']]);

        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Razorpay not configured'];
        }

        try {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post($this->baseUrl . '/orders', [
                    'amount' => $params['amount'] * 100, // Convert to paise
                    'currency' => $params['currency'] ?? 'INR',
                    'receipt' => $params['receipt'] ?? 'order_' . time(),
                    'notes' => $params['notes'] ?? [],
                ]);

            if ($response->successful()) {
                Log::info('RazorpayService: Order created', ['order_id' => $response->json('id')]);
                return [
                    'success' => true,
                    'order_id' => $response->json('id'),
                    'amount' => $params['amount'],
                    'currency' => $params['currency'] ?? 'INR',
                ];
            }

            Log::error('RazorpayService: Order creation failed', ['response' => $response->body()]);
            return ['success' => false, 'error' => $response->json('error.description') ?? 'Order creation failed'];
        } catch (\Throwable $e) {
            Log::error('RazorpayService: Order exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a payment link (shareable)
     */
    public function createPaymentLink(array $params): array
    {
        Log::info('RazorpayService: Creating payment link', ['amount' => $params['amount']]);

        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Razorpay not configured'];
        }

        try {
            $data = [
                'amount' => $params['amount'] * 100,
                'currency' => $params['currency'] ?? 'INR',
                'accept_partial' => $params['accept_partial'] ?? false,
                'description' => $params['description'] ?? 'Payment',
                'customer' => [
                    'name' => $params['customer_name'],
                    'contact' => $params['customer_phone'],
                    'email' => $params['customer_email'] ?? null,
                ],
                'notify' => [
                    'sms' => true,
                    'email' => !empty($params['customer_email']),
                ],
                'reminder_enable' => true,
                'notes' => $params['notes'] ?? [],
                'callback_url' => $params['callback_url'] ?? null,
                'callback_method' => 'get',
            ];

            if (!empty($params['expire_by'])) {
                $data['expire_by'] = $params['expire_by'];
            }

            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post($this->baseUrl . '/payment_links', $data);

            if ($response->successful()) {
                Log::info('RazorpayService: Payment link created', ['link_id' => $response->json('id')]);
                return [
                    'success' => true,
                    'link_id' => $response->json('id'),
                    'short_url' => $response->json('short_url'),
                    'amount' => $params['amount'],
                ];
            }

            Log::error('RazorpayService: Payment link creation failed', ['response' => $response->body()]);
            return ['success' => false, 'error' => $response->json('error.description') ?? 'Link creation failed'];
        } catch (\Throwable $e) {
            Log::error('RazorpayService: Payment link exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a QR code for UPI payments
     */
    public function createQRCode(array $params): array
    {
        Log::info('RazorpayService: Creating QR code', ['amount' => $params['amount'] ?? 'dynamic']);

        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Razorpay not configured'];
        }

        try {
            $data = [
                'type' => $params['type'] ?? 'upi_qr',
                'name' => $params['name'],
                'usage' => $params['usage'] ?? 'single_use',
                'description' => $params['description'] ?? 'Payment QR',
                'notes' => $params['notes'] ?? [],
            ];

            if (!empty($params['amount'])) {
                $data['fixed_amount'] = true;
                $data['payment_amount'] = $params['amount'] * 100;
            } else {
                $data['fixed_amount'] = false;
            }

            if (!empty($params['close_by'])) {
                $data['close_by'] = $params['close_by'];
            }

            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post($this->baseUrl . '/payments/qr_codes', $data);

            if ($response->successful()) {
                Log::info('RazorpayService: QR code created', ['qr_id' => $response->json('id')]);
                return [
                    'success' => true,
                    'qr_id' => $response->json('id'),
                    'image_url' => $response->json('image_url'),
                    'amount' => $params['amount'] ?? null,
                ];
            }

            Log::error('RazorpayService: QR code creation failed', ['response' => $response->body()]);
            return ['success' => false, 'error' => $response->json('error.description') ?? 'QR creation failed'];
        } catch (\Throwable $e) {
            Log::error('RazorpayService: QR code exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify payment signature
     */
    public function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $generatedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);
        return hash_equals($generatedSignature, $signature);
    }

    /**
     * Fetch payment details
     */
    public function fetchPayment(string $paymentId): array
    {
        Log::info('RazorpayService: Fetching payment', ['payment_id' => $paymentId]);

        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Razorpay not configured'];
        }

        try {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->get($this->baseUrl . '/payments/' . $paymentId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'payment' => $response->json(),
                ];
            }

            return ['success' => false, 'error' => 'Payment not found'];
        } catch (\Throwable $e) {
            Log::error('RazorpayService: Fetch payment exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Initiate refund
     */
    public function initiateRefund(string $paymentId, int $amount, array $notes = []): array
    {
        Log::info('RazorpayService: Initiating refund', ['payment_id' => $paymentId, 'amount' => $amount]);

        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Razorpay not configured'];
        }

        try {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post($this->baseUrl . '/payments/' . $paymentId . '/refund', [
                    'amount' => $amount * 100,
                    'speed' => 'normal',
                    'notes' => $notes,
                ]);

            if ($response->successful()) {
                Log::info('RazorpayService: Refund initiated', ['refund_id' => $response->json('id')]);
                return [
                    'success' => true,
                    'refund_id' => $response->json('id'),
                    'amount' => $amount,
                ];
            }

            Log::error('RazorpayService: Refund failed', ['response' => $response->body()]);
            return ['success' => false, 'error' => $response->json('error.description') ?? 'Refund failed'];
        } catch (\Throwable $e) {
            Log::error('RazorpayService: Refund exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create invoice payment link for an invoice
     */
    public function createInvoicePaymentLink(Invoice $invoice, Patient $patient, Clinic $clinic): array
    {
        $description = "Invoice #{$invoice->invoice_number} - {$clinic->name}";
        $expireBy = now()->addDays(7)->timestamp;

        $balance = max(0, (float) $invoice->total - (float) $invoice->paid);
        Log::info('RazorpayService: createInvoicePaymentLink balance', [
            'invoice_id' => $invoice->id,
            'balance' => $balance,
        ]);

        return $this->createPaymentLink([
            'amount' => $balance,
            'description' => $description,
            'customer_name' => $patient->name,
            'customer_phone' => $patient->phone,
            'customer_email' => $patient->email,
            'expire_by' => $expireBy,
            'notes' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
            ],
        ]);
    }

    /**
     * Get Razorpay key for frontend
     */
    public function getKeyId(): ?string
    {
        return $this->keyId;
    }
}
