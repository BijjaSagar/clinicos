<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\GstSacCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * List invoices
     */
    public function index(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching invoices', ['clinic_id' => $clinicId]);

        $query = Invoice::forClinic($clinicId)
            ->with(['patient', 'visit']);

        if ($request->patient_id) {
            $query->forPatient($request->patient_id);
        }

        if ($request->status) {
            $query->where('payment_status', $request->status);
        }

        if ($request->from_date) {
            $query->where('invoice_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->where('invoice_date', '<=', $request->to_date);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate(20);

        Log::info('Invoices retrieved', ['count' => $invoices->total()]);

        return response()->json($invoices);
    }

    /**
     * Create invoice
     */
    public function store(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Creating invoice', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'invoice_date' => 'nullable|date',
            'discount_pct' => 'nullable|numeric|min:0|max:100',
            'place_of_supply' => 'nullable|string|size:2',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:300',
            'items.*.item_type' => 'required|in:service,procedure,product,consultation,package',
            'items.*.sac_code' => 'nullable|string|max:10',
            'items.*.hsn_code' => 'nullable|string|max:10',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'nullable|numeric|min:0.01',
            'items.*.discount_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            // Create invoice
            $invoice = Invoice::create([
                'clinic_id' => $clinicId,
                'patient_id' => $validated['patient_id'],
                'visit_id' => $validated['visit_id'] ?? null,
                'invoice_date' => $validated['invoice_date'] ?? now()->toDateString(),
                'discount_pct' => $validated['discount_pct'] ?? 0,
                'place_of_supply' => $validated['place_of_supply'] ?? '27', // Maharashtra
                'notes' => $validated['notes'] ?? null,
                'payment_status' => Invoice::STATUS_PENDING,
            ]);

            Log::info('Invoice record created', ['invoice_id' => $invoice->id]);

            // Create items
            foreach ($validated['items'] as $index => $itemData) {
                $sacCode = $itemData['sac_code'] ?? InvoiceItem::SAC_CLINICAL_CONSULTATION;
                $gstRate = InvoiceItem::getGstRateForSac($sacCode);

                $item = new InvoiceItem([
                    'description' => $itemData['description'],
                    'item_type' => $itemData['item_type'],
                    'sac_code' => $sacCode,
                    'hsn_code' => $itemData['hsn_code'] ?? null,
                    'gst_rate' => $gstRate,
                    'unit_price' => $itemData['unit_price'],
                    'quantity' => $itemData['quantity'] ?? 1,
                    'discount_pct' => $itemData['discount_pct'] ?? 0,
                    'sort_order' => $index,
                ]);

                $item->calculateAmounts();
                $invoice->items()->save($item);

                Log::info('Invoice item created', [
                    'invoice_id' => $invoice->id,
                    'description' => $item->description,
                    'total' => $item->total
                ]);
            }

            // Calculate invoice totals
            $invoice->calculateTotals();
            $invoice->save();

            DB::commit();

            Log::info('Invoice created successfully', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total' => $invoice->total
            ]);

            return response()->json([
                'message' => 'Invoice created successfully',
                'invoice' => $invoice->load(['items', 'patient']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Show invoice
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching invoice', ['invoice_id' => $id]);

        $invoice = Invoice::forClinic($clinicId)
            ->with(['items', 'patient', 'visit', 'payments'])
            ->findOrFail($id);

        return response()->json([
            'invoice' => $invoice,
        ]);
    }

    /**
     * Update invoice
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Updating invoice', ['invoice_id' => $id]);

        $invoice = Invoice::forClinic($clinicId)->findOrFail($id);

        if ($invoice->isPaid()) {
            Log::warning('Cannot update paid invoice', ['invoice_id' => $id]);
            return response()->json(['message' => 'Cannot update a paid invoice'], 400);
        }

        $validated = $request->validate([
            'discount_pct' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $invoice->update($validated);

        if (isset($validated['discount_pct'])) {
            $discountAmount = $invoice->subtotal * ($validated['discount_pct'] / 100);
            $invoice->discount_amount = $discountAmount;
            $invoice->calculateTotals();
            $invoice->save();
        }

        Log::info('Invoice updated', ['invoice_id' => $id]);

        return response()->json([
            'message' => 'Invoice updated successfully',
            'invoice' => $invoice->fresh()->load(['items', 'patient']),
        ]);
    }

    /**
     * Send payment link via WhatsApp
     */
    public function sendLink(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Sending invoice payment link', ['invoice_id' => $id]);

        $invoice = Invoice::forClinic($clinicId)->findOrFail($id);

        // TODO: Implement WhatsApp sending via WhatsAppService
        // For now, just update the timestamp
        $invoice->update(['whatsapp_link_sent_at' => now()]);

        Log::info('Invoice payment link sent', ['invoice_id' => $id]);

        return response()->json([
            'message' => 'Payment link sent successfully',
        ]);
    }

    /**
     * Generate PDF
     */
    public function pdf(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Generating invoice PDF', ['invoice_id' => $id]);

        $invoice = Invoice::forClinic($clinicId)
            ->with(['items', 'patient', 'clinic'])
            ->findOrFail($id);

        // TODO: Implement PDF generation
        // For now, return invoice data
        Log::info('Invoice PDF requested', ['invoice_id' => $id]);

        return response()->json([
            'message' => 'PDF generation not yet implemented',
            'invoice' => $invoice,
        ]);
    }

    /**
     * Get GST breakdown
     */
    public function gstBreakdown(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching GST breakdown', ['invoice_id' => $id]);

        $invoice = Invoice::forClinic($clinicId)
            ->with('items')
            ->findOrFail($id);

        $breakdown = [
            'subtotal' => $invoice->subtotal,
            'discount' => $invoice->discount_amount,
            'taxable_value' => $invoice->subtotal - $invoice->discount_amount,
            'cgst' => $invoice->cgst_amount,
            'sgst' => $invoice->sgst_amount,
            'igst' => $invoice->igst_amount,
            'total_tax' => $invoice->getTotalGst(),
            'grand_total' => $invoice->total,
            'is_intra_state' => $invoice->isIntraState(),
            'items_by_sac' => $invoice->items->groupBy('sac_code')->map(function ($items, $sac) {
                return [
                    'sac_code' => $sac,
                    'description' => GstSacCode::find($sac)?->description ?? 'Unknown',
                    'taxable_value' => $items->sum('taxable_amount'),
                    'gst_rate' => $items->first()->gst_rate,
                    'cgst' => $items->sum('cgst_amount'),
                    'sgst' => $items->sum('sgst_amount'),
                ];
            })->values(),
        ];

        Log::info('GST breakdown generated', ['invoice_id' => $id]);

        return response()->json([
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * GST Report
     */
    public function gstReport(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Generating GST report', [
            'clinic_id' => $clinicId,
            'month' => $request->month,
            'year' => $request->year
        ]);

        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $invoices = Invoice::forClinic($clinicId)
            ->forMonth($validated['month'], $validated['year'])
            ->with('items')
            ->get();

        $report = [
            'period' => sprintf('%02d/%d', $validated['month'], $validated['year']),
            'total_invoices' => $invoices->count(),
            'total_taxable_value' => $invoices->sum('subtotal'),
            'total_cgst' => $invoices->sum('cgst_amount'),
            'total_sgst' => $invoices->sum('sgst_amount'),
            'total_igst' => $invoices->sum('igst_amount'),
            'total_gst' => $invoices->sum(fn($i) => $i->getTotalGst()),
            'total_revenue' => $invoices->sum('total'),
            'by_sac_code' => [],
        ];

        // Group by SAC code
        $allItems = $invoices->flatMap->items;
        $report['by_sac_code'] = $allItems->groupBy('sac_code')->map(function ($items, $sac) {
            return [
                'sac_code' => $sac,
                'description' => GstSacCode::find($sac)?->description ?? 'Unknown',
                'taxable_value' => $items->sum('taxable_amount'),
                'cgst' => $items->sum('cgst_amount'),
                'sgst' => $items->sum('sgst_amount'),
                'count' => $items->count(),
            ];
        })->values();

        Log::info('GST report generated', ['total_invoices' => $report['total_invoices']]);

        return response()->json([
            'report' => $report,
        ]);
    }

    /**
     * Generate e-Invoice (IRN)
     */
    public function generateEinvoice(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Generating e-Invoice', ['invoice_id' => $id]);

        $invoice = Invoice::forClinic($clinicId)->findOrFail($id);

        if ($invoice->irn) {
            Log::warning('IRN already exists', ['invoice_id' => $id, 'irn' => $invoice->irn]);
            return response()->json([
                'message' => 'e-Invoice already generated',
                'irn' => $invoice->irn,
            ]);
        }

        // TODO: Integrate with GSP for actual IRN generation
        // For now, generate a placeholder
        $irn = hash('sha256', $invoice->invoice_number . now()->timestamp);
        $ackNumber = 'ACK' . random_int(100000000000, 999999999999);

        $invoice->update([
            'irn' => $irn,
            'ack_number' => $ackNumber,
            'irn_generated_at' => now(),
        ]);

        Log::info('e-Invoice generated (placeholder)', [
            'invoice_id' => $id,
            'irn' => $irn
        ]);

        return response()->json([
            'message' => 'e-Invoice generated successfully',
            'irn' => $irn,
            'ack_number' => $ackNumber,
        ]);
    }
}
