<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Patient;
use App\Models\GstSacCode;
use App\Models\WhatsappMessage;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BillingController extends Controller
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
    ) {}

    // -------------------------------------------------------------------------
    // Public endpoints
    // -------------------------------------------------------------------------

    /**
     * GET /billing/invoices
     * Paginated invoice list for the authenticated clinic.
     * Query params: status, date_from, date_to, patient_id, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $request->validate([
            'status'     => 'nullable|string|in:draft,finalized,paid,partially_paid,cancelled',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
            'patient_id' => 'nullable|integer|exists:patients,id',
            'per_page'   => 'nullable|integer|min:5|max:100',
        ]);

        $query = Invoice::with(['patient:id,name,phone', 'payments'])
            ->where('clinic_id', $clinicId)
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->date_from, fn($q, $d) => $q->whereDate('invoice_date', '>=', $d))
            ->when($request->date_to, fn($q, $d) => $q->whereDate('invoice_date', '<=', $d))
            ->when($request->patient_id, fn($q, $pid) => $q->where('patient_id', $pid))
            ->latest('invoice_date');

        $perPage   = (int) ($request->per_page ?? 20);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data'    => $paginated->items(),
            'message' => 'Invoice list retrieved successfully.',
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /billing/invoices/{id}
     * Single invoice with line items, payment history, and GST breakdown.
     */
    public function show(int $id): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $invoice = Invoice::with([
            'patient',
            'items.gstSacCode',
            'payments',
            'clinic',
        ])
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        return response()->json([
            'data'    => $this->buildInvoiceArray($invoice),
            'message' => 'Invoice retrieved successfully.',
            'meta'    => [],
        ]);
    }

    /**
     * POST /billing/invoices
     * Create a draft invoice. GST is calculated automatically from line items.
     */
    public function store(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $validated = $request->validate([
            'patient_id'            => 'required|integer|exists:patients,id',
            'visit_id'              => 'nullable|integer|exists:visits,id',
            'invoice_date'          => 'required|date',
            'due_date'              => 'nullable|date|after_or_equal:invoice_date',
            'notes'                 => 'nullable|string|max:1000',
            'discount_amount'       => 'nullable|numeric|min:0',
            'items'                 => 'required|array|min:1',
            'items.*.description'   => 'required|string|max:255',
            'items.*.sac_code_id'   => 'nullable|integer|exists:gst_sac_codes,id',
            'items.*.quantity'      => 'required|numeric|min:0.01',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.drug_id'       => 'nullable|integer|exists:indian_drugs,id',
        ]);

        // Verify patient belongs to same clinic
        $patient = Patient::where('clinic_id', $clinicId)->findOrFail($validated['patient_id']);

        DB::beginTransaction();
        try {
            $gstItems = $this->calculateGst($validated['items'], $clinicId, $patient->id);

            $subtotal      = collect($gstItems)->sum('line_total');
            $totalCgst     = collect($gstItems)->sum('cgst_amount');
            $totalSgst     = collect($gstItems)->sum('sgst_amount');
            $totalIgst     = collect($gstItems)->sum('igst_amount');
            $discountAmt   = (float) ($validated['discount_amount'] ?? 0);
            $grandTotal    = $subtotal + $totalCgst + $totalSgst + $totalIgst - $discountAmt;

            $invoice = Invoice::create([
                'clinic_id'       => $clinicId,
                'patient_id'      => $validated['patient_id'],
                'visit_id'        => $validated['visit_id'] ?? null,
                'invoice_date'    => $validated['invoice_date'],
                'due_date'        => $validated['due_date'] ?? null,
                'status'          => 'draft',
                'subtotal'        => $subtotal,
                'cgst_total'      => $totalCgst,
                'sgst_total'      => $totalSgst,
                'igst_total'      => $totalIgst,
                'discount_amount' => $discountAmt,
                'grand_total'     => max(0, $grandTotal),
                'amount_paid'     => 0,
                'notes'           => $validated['notes'] ?? null,
            ]);

            foreach ($gstItems as $item) {
                InvoiceItem::create(array_merge($item, ['invoice_id' => $invoice->id]));
            }

            DB::commit();

            $invoice->load(['patient', 'items.gstSacCode', 'payments', 'clinic']);

            return response()->json([
                'data'    => $this->buildInvoiceArray($invoice),
                'message' => 'Draft invoice created successfully.',
                'meta'    => [],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('BillingController@store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'data'    => null,
                'message' => 'Failed to create invoice: ' . $e->getMessage(),
                'meta'    => [],
            ], 500);
        }
    }

    /**
     * PUT /billing/invoices/{id}
     * Update a draft invoice. Finalized invoices cannot be edited.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $invoice = Invoice::where('clinic_id', $clinicId)->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return response()->json([
                'data'    => null,
                'message' => 'Only draft invoices can be edited.',
                'meta'    => [],
            ], 422);
        }

        $validated = $request->validate([
            'invoice_date'          => 'nullable|date',
            'due_date'              => 'nullable|date',
            'notes'                 => 'nullable|string|max:1000',
            'discount_amount'       => 'nullable|numeric|min:0',
            'items'                 => 'nullable|array|min:1',
            'items.*.description'   => 'required_with:items|string|max:255',
            'items.*.sac_code_id'   => 'nullable|integer|exists:gst_sac_codes,id',
            'items.*.quantity'      => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price'    => 'required_with:items|numeric|min:0',
            'items.*.drug_id'       => 'nullable|integer|exists:indian_drugs,id',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['items'])) {
                $patient     = Patient::findOrFail($invoice->patient_id);
                $gstItems    = $this->calculateGst($validated['items'], $clinicId, $patient->id);

                $subtotal    = collect($gstItems)->sum('line_total');
                $totalCgst   = collect($gstItems)->sum('cgst_amount');
                $totalSgst   = collect($gstItems)->sum('sgst_amount');
                $totalIgst   = collect($gstItems)->sum('igst_amount');
                $discountAmt = (float) ($validated['discount_amount'] ?? $invoice->discount_amount);
                $grandTotal  = $subtotal + $totalCgst + $totalSgst + $totalIgst - $discountAmt;

                $invoice->items()->delete();
                foreach ($gstItems as $item) {
                    InvoiceItem::create(array_merge($item, ['invoice_id' => $invoice->id]));
                }

                $invoice->fill([
                    'subtotal'        => $subtotal,
                    'cgst_total'      => $totalCgst,
                    'sgst_total'      => $totalSgst,
                    'igst_total'      => $totalIgst,
                    'discount_amount' => $discountAmt,
                    'grand_total'     => max(0, $grandTotal),
                ]);
            }

            $invoice->fill(array_filter([
                'invoice_date'    => $validated['invoice_date'] ?? null,
                'due_date'        => $validated['due_date'] ?? null,
                'notes'           => $validated['notes'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? null,
            ], fn($v) => $v !== null));

            $invoice->save();

            DB::commit();

            $invoice->load(['patient', 'items.gstSacCode', 'payments', 'clinic']);

            return response()->json([
                'data'    => $this->buildInvoiceArray($invoice),
                'message' => 'Invoice updated successfully.',
                'meta'    => [],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('BillingController@update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'data'    => null,
                'message' => 'Failed to update invoice: ' . $e->getMessage(),
                'meta'    => [],
            ], 500);
        }
    }

    /**
     * POST /billing/invoices/{id}/finalize
     * Locks the invoice, assigns an invoice number, optionally sends WhatsApp,
     * and generates an e-Invoice IRN stub.
     */
    public function finalize(int $id): JsonResponse
    {
        $user     = auth()->user();
        $clinicId = $user->clinic_id;

        $invoice = Invoice::with(['patient', 'items.gstSacCode', 'payments', 'clinic'])
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return response()->json([
                'data'    => null,
                'message' => 'Invoice is already finalized or cancelled.',
                'meta'    => [],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $invoiceNumber = $this->generateInvoiceNumber($clinicId);
            $irn           = $this->generateIrnStub($invoice, $invoiceNumber);

            $invoice->update([
                'status'         => 'finalized',
                'invoice_number' => $invoiceNumber,
                'finalized_at'   => now(),
                'irn'            => $irn,
            ]);

            DB::commit();

            // Trigger WhatsApp if patient opted in
            $patient = $invoice->patient;
            if ($patient && $patient->whatsapp_opted_in && $patient->phone) {
                try {
                    $this->whatsAppService->send(
                        $this->formatPhoneNumber($patient->phone),
                        'invoice_ready',
                        [
                            ['type' => 'body', 'parameters' => [
                                ['type' => 'text', 'text' => $patient->name],
                                ['type' => 'text', 'text' => $invoiceNumber],
                                ['type' => 'text', 'text' => number_format($invoice->grand_total, 2)],
                            ]],
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::warning('WhatsApp send failed after finalize', ['invoice_id' => $id, 'error' => $e->getMessage()]);
                }
            }

            return response()->json([
                'data'    => $this->buildInvoiceArray($invoice->fresh(['patient', 'items.gstSacCode', 'payments', 'clinic'])),
                'message' => 'Invoice finalized successfully.',
                'meta'    => ['invoice_number' => $invoiceNumber, 'irn' => $irn],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('BillingController@finalize failed', ['error' => $e->getMessage()]);
            return response()->json([
                'data'    => null,
                'message' => 'Failed to finalize invoice: ' . $e->getMessage(),
                'meta'    => [],
            ], 500);
        }
    }

    /**
     * DELETE /billing/invoices/{id}
     * Soft-delete a draft invoice only.
     */
    public function destroy(int $id): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $invoice = Invoice::where('clinic_id', $clinicId)->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return response()->json([
                'data'    => null,
                'message' => 'Only draft invoices can be deleted.',
                'meta'    => [],
            ], 422);
        }

        $invoice->delete(); // soft delete via SoftDeletes trait

        return response()->json([
            'data'    => null,
            'message' => 'Draft invoice deleted successfully.',
            'meta'    => [],
        ]);
    }

    /**
     * POST /billing/invoices/{id}/payment
     * Record a payment against a finalized invoice.
     */
    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $invoice = Invoice::with(['patient', 'payments'])
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        if (!in_array($invoice->status, ['finalized', 'partially_paid'])) {
            return response()->json([
                'data'    => null,
                'message' => 'Payment can only be recorded for finalized or partially paid invoices.',
                'meta'    => [],
            ], 422);
        }

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,card,upi,neft,rtgs,cheque,razorpay',
            'payment_date'   => 'required|date',
            'reference'      => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:500',
        ]);

        $outstanding = $invoice->grand_total - $invoice->amount_paid;

        if ($validated['amount'] > $outstanding + 0.01) {
            return response()->json([
                'data'    => null,
                'message' => sprintf(
                    'Payment amount ₹%s exceeds outstanding ₹%s.',
                    number_format($validated['amount'], 2),
                    number_format($outstanding, 2)
                ),
                'meta'    => [],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $payment = Payment::create([
                'clinic_id'      => $clinicId,
                'invoice_id'     => $invoice->id,
                'patient_id'     => $invoice->patient_id,
                'amount'         => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date'   => $validated['payment_date'],
                'reference'      => $validated['reference'] ?? null,
                'notes'          => $validated['notes'] ?? null,
                'status'         => 'success',
            ]);

            $newAmountPaid = $invoice->amount_paid + $validated['amount'];
            $newStatus     = ($newAmountPaid >= $invoice->grand_total - 0.01) ? 'paid' : 'partially_paid';

            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'status'      => $newStatus,
            ]);

            DB::commit();

            // Send WhatsApp receipt
            $patient = $invoice->patient;
            if ($patient && $patient->whatsapp_opted_in && $patient->phone) {
                try {
                    $this->whatsAppService->send(
                        $this->formatPhoneNumber($patient->phone),
                        'payment_receipt',
                        [
                            ['type' => 'body', 'parameters' => [
                                ['type' => 'text', 'text' => $patient->name],
                                ['type' => 'text', 'text' => number_format($validated['amount'], 2)],
                                ['type' => 'text', 'text' => $invoice->invoice_number],
                                ['type' => 'text', 'text' => Carbon::parse($validated['payment_date'])->format('d M Y')],
                            ]],
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::warning('WhatsApp receipt failed', ['invoice_id' => $id, 'error' => $e->getMessage()]);
                }
            }

            return response()->json([
                'data'    => ['payment' => $payment, 'invoice_status' => $newStatus, 'amount_paid' => $newAmountPaid],
                'message' => 'Payment recorded successfully.',
                'meta'    => [],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('BillingController@recordPayment failed', ['error' => $e->getMessage()]);
            return response()->json([
                'data'    => null,
                'message' => 'Failed to record payment: ' . $e->getMessage(),
                'meta'    => [],
            ], 500);
        }
    }

    /**
     * POST /billing/invoices/{id}/razorpay/initiate
     * Create a Razorpay order and return order_id + publishable key.
     */
    public function initiateRazorpay(int $id): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $invoice = Invoice::where('clinic_id', $clinicId)->findOrFail($id);

        if (!in_array($invoice->status, ['finalized', 'partially_paid'])) {
            return response()->json([
                'data'    => null,
                'message' => 'Razorpay order can only be created for finalized or partially paid invoices.',
                'meta'    => [],
            ], 422);
        }

        $outstanding  = $invoice->grand_total - $invoice->amount_paid;
        $amountPaisa  = (int) round($outstanding * 100); // Razorpay expects paise

        try {
            $response = Http::withBasicAuth(
                config('services.razorpay.key_id'),
                config('services.razorpay.key_secret')
            )->post('https://api.razorpay.com/v1/orders', [
                'amount'          => $amountPaisa,
                'currency'        => 'INR',
                'receipt'         => 'invoice_' . $invoice->id,
                'notes'           => [
                    'invoice_id'     => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'clinic_id'      => $clinicId,
                ],
            ]);

            if ($response->failed()) {
                throw new \RuntimeException('Razorpay order creation failed: ' . $response->body());
            }

            $order = $response->json();

            $invoice->update(['razorpay_order_id' => $order['id']]);

            return response()->json([
                'data'    => [
                    'order_id'   => $order['id'],
                    'amount'     => $amountPaisa,
                    'currency'   => 'INR',
                    'key_id'     => config('services.razorpay.key_id'),
                    'invoice_id' => $invoice->id,
                ],
                'message' => 'Razorpay order created successfully.',
                'meta'    => [],
            ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay initiate failed', ['invoice_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'data'    => null,
                'message' => 'Failed to initiate Razorpay payment: ' . $e->getMessage(),
                'meta'    => [],
            ], 500);
        }
    }

    /**
     * POST /billing/invoices/{id}/razorpay/verify
     * Verify Razorpay webhook HMAC SHA256 signature and mark payment success.
     */
    public function verifyRazorpay(Request $request, int $id): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $invoice = Invoice::where('clinic_id', $clinicId)->findOrFail($id);

        $validated = $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        // Verify HMAC SHA256
        $expectedSignature = hash_hmac(
            'sha256',
            $validated['razorpay_order_id'] . '|' . $validated['razorpay_payment_id'],
            config('services.razorpay.key_secret')
        );

        if (!hash_equals($expectedSignature, $validated['razorpay_signature'])) {
            Log::warning('Razorpay signature mismatch', ['invoice_id' => $id]);
            return response()->json([
                'data'    => null,
                'message' => 'Invalid payment signature. Verification failed.',
                'meta'    => [],
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Fetch payment details from Razorpay
            $response = Http::withBasicAuth(
                config('services.razorpay.key_id'),
                config('services.razorpay.key_secret')
            )->get('https://api.razorpay.com/v1/payments/' . $validated['razorpay_payment_id']);

            $rpPayment = $response->json();
            $amountINR = ($rpPayment['amount'] ?? 0) / 100;

            $payment = Payment::create([
                'clinic_id'             => $clinicId,
                'invoice_id'            => $invoice->id,
                'patient_id'            => $invoice->patient_id,
                'amount'                => $amountINR,
                'payment_method'        => 'razorpay',
                'payment_date'          => now()->toDateString(),
                'reference'             => $validated['razorpay_payment_id'],
                'razorpay_order_id'     => $validated['razorpay_order_id'],
                'razorpay_payment_id'   => $validated['razorpay_payment_id'],
                'razorpay_signature'    => $validated['razorpay_signature'],
                'status'                => 'success',
            ]);

            $newAmountPaid = $invoice->amount_paid + $amountINR;
            $newStatus     = ($newAmountPaid >= $invoice->grand_total - 0.01) ? 'paid' : 'partially_paid';

            $invoice->update([
                'amount_paid'          => $newAmountPaid,
                'status'               => $newStatus,
                'razorpay_payment_id'  => $validated['razorpay_payment_id'],
                'razorpay_signature'   => $validated['razorpay_signature'],
            ]);

            DB::commit();

            return response()->json([
                'data'    => ['payment' => $payment, 'invoice_status' => $newStatus],
                'message' => 'Razorpay payment verified and recorded successfully.',
                'meta'    => [],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Razorpay verify failed', ['invoice_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'data'    => null,
                'message' => 'Failed to verify Razorpay payment: ' . $e->getMessage(),
                'meta'    => [],
            ], 500);
        }
    }

    /**
     * GET /billing/invoices/{id}/pdf
     * Return base64-encoded PDF stub for the invoice.
     * In production: replace stub with Snappy/DomPDF rendering.
     */
    public function generatePdf(int $id): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $invoice = Invoice::with(['patient', 'items.gstSacCode', 'payments', 'clinic'])
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        // Production: use Snappy or DomPDF
        // $pdf = PDF::loadView('invoices.pdf', ['invoice' => $invoice]);
        // $base64 = base64_encode($pdf->output());

        $stubContent = sprintf(
            "INVOICE PDF STUB\nInvoice: %s\nPatient: %s\nTotal: ₹%s\nGenerated: %s",
            $invoice->invoice_number ?? 'DRAFT',
            $invoice->patient->name ?? 'N/A',
            number_format($invoice->grand_total, 2),
            now()->toDateTimeString()
        );
        $base64 = base64_encode($stubContent);

        return response()->json([
            'data'    => [
                'pdf_base64'  => $base64,
                'filename'    => 'invoice_' . ($invoice->invoice_number ?? $invoice->id) . '.pdf',
                'mime_type'   => 'application/pdf',
                'invoice_id'  => $invoice->id,
            ],
            'message' => 'PDF generated successfully.',
            'meta'    => [],
        ]);
    }

    /**
     * POST /billing/invoices/{id}/whatsapp
     * Trigger WhatsApp payment link message for the invoice.
     */
    public function sendWhatsApp(int $id): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $invoice = Invoice::with('patient')
            ->where('clinic_id', $clinicId)
            ->findOrFail($id);

        if (!$invoice->patient || !$invoice->patient->phone) {
            return response()->json([
                'data'    => null,
                'message' => 'Patient phone number not available.',
                'meta'    => [],
            ], 422);
        }

        try {
            $outstanding  = $invoice->grand_total - $invoice->amount_paid;
            $paymentLink  = route('payment.link', ['invoice' => $invoice->id]);

            $result = $this->whatsAppService->send(
                $this->formatPhoneNumber($invoice->patient->phone),
                'payment_link',
                [
                    ['type' => 'body', 'parameters' => [
                        ['type' => 'text', 'text' => $invoice->patient->name],
                        ['type' => 'text', 'text' => $invoice->invoice_number ?? ('INV-' . $invoice->id)],
                        ['type' => 'text', 'text' => number_format($outstanding, 2)],
                        ['type' => 'text', 'text' => $paymentLink],
                    ]],
                ]
            );

            return response()->json([
                'data'    => $result,
                'message' => 'WhatsApp payment link sent successfully.',
                'meta'    => [],
            ]);
        } catch (\Throwable $e) {
            Log::error('BillingController@sendWhatsApp failed', ['invoice_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'data'    => null,
                'message' => 'Failed to send WhatsApp message: ' . $e->getMessage(),
                'meta'    => [],
            ], 500);
        }
    }

    /**
     * GET /billing/gst-summary
     * GST summary report grouped by SAC code.
     * Query params: month (YYYY-MM), year
     */
    public function gstSummary(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'year'  => 'nullable|integer|min:2020|max:2099',
        ]);

        $query = InvoiceItem::select([
                'invoice_items.sac_code_id',
                'gst_sac_codes.sac_code',
                'gst_sac_codes.description as sac_description',
                DB::raw('SUM(invoice_items.line_total) as taxable_value'),
                DB::raw('SUM(invoice_items.cgst_amount) as total_cgst'),
                DB::raw('SUM(invoice_items.sgst_amount) as total_sgst'),
                DB::raw('SUM(invoice_items.igst_amount) as total_igst'),
                DB::raw('COUNT(DISTINCT invoice_items.invoice_id) as invoice_count'),
            ])
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->leftJoin('gst_sac_codes', 'invoice_items.sac_code_id', '=', 'gst_sac_codes.id')
            ->where('invoices.clinic_id', $clinicId)
            ->whereIn('invoices.status', ['finalized', 'paid', 'partially_paid'])
            ->groupBy('invoice_items.sac_code_id', 'gst_sac_codes.sac_code', 'gst_sac_codes.description');

        if ($request->month) {
            [$year, $month] = explode('-', $request->month);
            $query->whereYear('invoices.invoice_date', $year)
                  ->whereMonth('invoices.invoice_date', $month);
        } elseif ($request->year) {
            $query->whereYear('invoices.invoice_date', $request->year);
        } else {
            // Default: current month
            $query->whereYear('invoices.invoice_date', now()->year)
                  ->whereMonth('invoices.invoice_date', now()->month);
        }

        $summary = $query->get();

        $totals = [
            'taxable_value' => $summary->sum('taxable_value'),
            'total_cgst'    => $summary->sum('total_cgst'),
            'total_sgst'    => $summary->sum('total_sgst'),
            'total_igst'    => $summary->sum('total_igst'),
        ];

        return response()->json([
            'data'    => $summary,
            'message' => 'GST summary retrieved successfully.',
            'meta'    => ['totals' => $totals, 'period' => $request->month ?? $request->year ?? now()->format('Y-m')],
        ]);
    }

    /**
     * GET /billing/outstanding
     * Outstanding invoices list with aging buckets.
     */
    public function outstandingReport(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $request->validate([
            'patient_id' => 'nullable|integer|exists:patients,id',
            'per_page'   => 'nullable|integer|min:5|max:100',
        ]);

        $today = Carbon::today();

        $query = Invoice::with(['patient:id,name,phone'])
            ->where('clinic_id', $clinicId)
            ->whereIn('status', ['finalized', 'partially_paid'])
            ->whereRaw('grand_total > amount_paid')
            ->when($request->patient_id, fn($q, $pid) => $q->where('patient_id', $pid));

        $perPage    = (int) ($request->per_page ?? 20);
        $paginated  = $query->orderBy('invoice_date')->paginate($perPage);

        $items = collect($paginated->items())->map(function (Invoice $inv) use ($today) {
            $daysPast   = $today->diffInDays(Carbon::parse($inv->invoice_date), false) * -1;
            $outstanding = $inv->grand_total - $inv->amount_paid;

            $agingBucket = match (true) {
                $daysPast <= 0  => 'current',
                $daysPast <= 30 => '0-30',
                $daysPast <= 60 => '31-60',
                $daysPast <= 90 => '61-90',
                default         => '90+',
            };

            return [
                'id'             => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'invoice_date'   => $inv->invoice_date,
                'due_date'       => $inv->due_date,
                'patient'        => $inv->patient,
                'grand_total'    => $inv->grand_total,
                'amount_paid'    => $inv->amount_paid,
                'outstanding'    => round($outstanding, 2),
                'days_overdue'   => max(0, $daysPast),
                'aging_bucket'   => $agingBucket,
            ];
        });

        // Aging summary
        $agingSummary = $items->groupBy('aging_bucket')->map(fn($g) => [
            'count'       => $g->count(),
            'outstanding' => round($g->sum('outstanding'), 2),
        ]);

        return response()->json([
            'data'    => $items,
            'message' => 'Outstanding report retrieved successfully.',
            'meta'    => [
                'current_page'   => $paginated->currentPage(),
                'last_page'      => $paginated->lastPage(),
                'per_page'       => $paginated->perPage(),
                'total'          => $paginated->total(),
                'aging_summary'  => $agingSummary,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Calculate CGST/SGST/IGST per line item.
     * Intra-state: CGST + SGST (half each); inter-state: full IGST.
     *
     * @param  array  $items     Raw items from request
     * @param  int    $clinicId
     * @param  int    $patientId
     * @return array             Enriched item array with tax fields
     */
    private function calculateGst(array $items, int $clinicId, int $patientId): array
    {
        $clinic  = Clinic::findOrFail($clinicId);
        $patient = Patient::findOrFail($patientId);

        $isIntraState = $clinic->state_code === $patient->state_code;

        $result = [];
        foreach ($items as $item) {
            $sacCode  = isset($item['sac_code_id'])
                ? GstSacCode::find($item['sac_code_id'])
                : null;

            $gstRate   = $sacCode ? (float) $sacCode->gst_rate : 0.0;
            $lineTotal = (float) $item['quantity'] * (float) $item['unit_price'];
            $taxAmount = round($lineTotal * $gstRate / 100, 2);

            if ($gstRate === 0.0) {
                $cgst = $sgst = $igst = 0.0;
            } elseif ($isIntraState) {
                $cgst = round($taxAmount / 2, 2);
                $sgst = $taxAmount - $cgst; // ensures cgst+sgst == taxAmount exactly
                $igst = 0.0;
            } else {
                $cgst = 0.0;
                $sgst = 0.0;
                $igst = $taxAmount;
            }

            $result[] = [
                'description'  => $item['description'],
                'sac_code_id'  => $item['sac_code_id'] ?? null,
                'drug_id'      => $item['drug_id'] ?? null,
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'line_total'   => round($lineTotal, 2),
                'gst_rate'     => $gstRate,
                'cgst_rate'    => $isIntraState ? $gstRate / 2 : 0,
                'sgst_rate'    => $isIntraState ? $gstRate / 2 : 0,
                'igst_rate'    => $isIntraState ? 0 : $gstRate,
                'cgst_amount'  => $cgst,
                'sgst_amount'  => $sgst,
                'igst_amount'  => $igst,
            ];
        }

        return $result;
    }

    /**
     * Generate an atomic invoice number in INV-{YYYY}-{NNNN} format per clinic per year.
     */
    private function generateInvoiceNumber(int $clinicId): string
    {
        $year = now()->year;

        // Use DB-level atomic increment to avoid race conditions
        $seq = DB::transaction(function () use ($clinicId, $year) {
            $row = DB::table('invoice_sequences')
                ->where('clinic_id', $clinicId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($row) {
                $nextSeq = $row->last_seq + 1;
                DB::table('invoice_sequences')
                    ->where('clinic_id', $clinicId)
                    ->where('year', $year)
                    ->update(['last_seq' => $nextSeq]);
            } else {
                $nextSeq = 1;
                DB::table('invoice_sequences')->insert([
                    'clinic_id' => $clinicId,
                    'year'      => $year,
                    'last_seq'  => $nextSeq,
                ]);
            }

            return $nextSeq;
        });

        return sprintf('INV-%d-%04d', $year, $seq);
    }

    /**
     * Generate an e-Invoice IRN stub (SHA256 of key fields).
     * In production: call NIC/IRP API for actual IRN.
     */
    private function generateIrnStub(Invoice $invoice, string $invoiceNumber): string
    {
        $clinic = Clinic::find($invoice->clinic_id);
        $gstin  = $clinic->gstin ?? 'UNREGISTERED';

        return hash('sha256', implode('|', [
            $gstin,
            'INV',
            $invoiceNumber,
            now()->format('Ymd'),
        ]));
    }

    /**
     * Format an invoice model into a consistent API response array.
     */
    private function buildInvoiceArray(Invoice $invoice): array
    {
        $gstBreakdown = [];
        foreach (($invoice->items ?? []) as $item) {
            $sacCode = $item->sac_code_id ?? 'NONE';
            if (!isset($gstBreakdown[$sacCode])) {
                $gstBreakdown[$sacCode] = [
                    'sac_code'     => $item->gstSacCode->sac_code ?? null,
                    'description'  => $item->gstSacCode->description ?? 'General',
                    'gst_rate'     => $item->gst_rate,
                    'taxable_value'=> 0,
                    'cgst'         => 0,
                    'sgst'         => 0,
                    'igst'         => 0,
                ];
            }
            $gstBreakdown[$sacCode]['taxable_value'] += $item->line_total;
            $gstBreakdown[$sacCode]['cgst']          += $item->cgst_amount;
            $gstBreakdown[$sacCode]['sgst']          += $item->sgst_amount;
            $gstBreakdown[$sacCode]['igst']          += $item->igst_amount;
        }

        return [
            'id'             => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status'         => $invoice->status,
            'invoice_date'   => $invoice->invoice_date,
            'due_date'       => $invoice->due_date,
            'finalized_at'   => $invoice->finalized_at ?? null,
            'irn'            => $invoice->irn ?? null,
            'patient'        => $invoice->patient,
            'clinic'         => [
                'id'       => $invoice->clinic->id ?? null,
                'name'     => $invoice->clinic->name ?? null,
                'gstin'    => $invoice->clinic->gstin ?? null,
                'address'  => $invoice->clinic->address ?? null,
            ],
            'items'          => $invoice->items,
            'subtotal'       => $invoice->subtotal,
            'cgst_total'     => $invoice->cgst_total,
            'sgst_total'     => $invoice->sgst_total,
            'igst_total'     => $invoice->igst_total,
            'discount_amount'=> $invoice->discount_amount,
            'grand_total'    => $invoice->grand_total,
            'amount_paid'    => $invoice->amount_paid,
            'outstanding'    => round($invoice->grand_total - $invoice->amount_paid, 2),
            'payments'       => $invoice->payments,
            'notes'          => $invoice->notes,
            'gst_breakdown'  => array_values($gstBreakdown),
        ];
    }

    /**
     * Format a phone number to E.164 with +91 prefix for Indian numbers.
     */
    private function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) === 10) {
            return '+91' . $phone;
        }
        if (strlen($phone) === 12 && str_starts_with($phone, '91')) {
            return '+' . $phone;
        }
        return '+' . ltrim($phone, '+');
    }
}
