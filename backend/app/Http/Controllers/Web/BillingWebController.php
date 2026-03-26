<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class BillingWebController extends Controller
{
    public function index(Request $request)
    {
        Log::info('BillingWebController@index', ['user' => auth()->id()]);

        try {
            $clinicId = auth()->user()->clinic_id;
            
            $query = Invoice::with(['patient', 'items', 'payments'])
                ->where('clinic_id', $clinicId)
                ->latest();

            if ($request->filled('status')) {
                $query->where('payment_status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('patient', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $invoices = $query->paginate(20);

            // Use correct column names from Invoice model
            $stats = [
                'total_today' => Invoice::where('clinic_id', $clinicId)
                    ->whereDate('created_at', today())
                    ->sum('total') ?? 0,
                'pending' => Invoice::where('clinic_id', $clinicId)
                    ->where('payment_status', 'pending')
                    ->selectRaw('SUM(total - paid) as balance')
                    ->value('balance') ?? 0,
                'collected_month' => Payment::whereHas('invoice', function ($q) use ($clinicId) {
                    $q->where('clinic_id', $clinicId);
                })->whereMonth('payment_date', now()->month)->sum('amount') ?? 0,
            ];

            Log::info('BillingWebController@index success', ['invoices_count' => $invoices->count()]);

            return view('billing.index', compact('invoices', 'stats'));
        } catch (\Throwable $e) {
            Log::error('BillingWebController@index error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    public function create()
    {
        Log::info('BillingWebController@create');

        $patients = Patient::where('clinic_id', auth()->user()->clinic_id)
            ->orderBy('name')
            ->get();

        return view('billing.create', compact('patients'));
    }

    public function store(Request $request)
    {
        Log::info('BillingWebController@store - Raw request', [
            'all' => $request->all(),
            'visit_id_raw' => $request->input('visit_id'),
            'visit_id_filled' => $request->filled('visit_id'),
        ]);

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|integer|exists:visits,id',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'nullable|numeric|min:1',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.sac_code' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $clinicId = auth()->user()->clinic_id;
            $clinic = auth()->user()->clinic;
            
            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $qty = $item['quantity'] ?? 1;
                $subtotal += $item['amount'] * $qty;
            }
            
            $discountAmount = $validated['discount_amount'] ?? 0;
            $taxableAmount = $subtotal - $discountAmount;
            
            // Get GST rate from clinic settings (default 18%)
            $gstRate = $clinic->settings['default_gst_rate'] ?? 18;
            $cgstRate = $gstRate / 2;
            $sgstRate = $gstRate / 2;
            
            $cgstAmount = round($taxableAmount * ($cgstRate / 100), 2);
            $sgstAmount = round($taxableAmount * ($sgstRate / 100), 2);
            $total = $taxableAmount + $cgstAmount + $sgstAmount;

            // Ensure visit_id is properly set (handle empty string as null)
            $visitId = !empty($validated['visit_id']) ? (int)$validated['visit_id'] : null;
            
            Log::info('Creating invoice with visit_id', ['visit_id' => $visitId, 'validated_visit_id' => $validated['visit_id'] ?? 'not set']);

            // Create invoice
            $invoice = Invoice::create([
                'clinic_id' => $clinicId,
                'patient_id' => $validated['patient_id'],
                'visit_id' => $visitId,
                'invoice_date' => now(),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'total' => $total,
                'paid' => 0,
                'payment_status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create invoice items
            foreach ($validated['items'] as $index => $item) {
                $qty = $item['quantity'] ?? 1;
                $itemAmount = $item['amount'] * $qty;
                $itemCgst = round($itemAmount * ($cgstRate / 100), 2);
                $itemSgst = round($itemAmount * ($sgstRate / 100), 2);
                $itemTotal = $itemAmount + $itemCgst + $itemSgst;
                
                \App\Models\InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'item_type' => 'service',
                    'sac_code' => $item['sac_code'] ?? '999312', // Default healthcare SAC
                    'gst_rate' => $gstRate,
                    'unit_price' => $item['amount'],
                    'quantity' => $qty,
                    'taxable_amount' => $itemAmount,
                    'cgst_amount' => $itemCgst,
                    'sgst_amount' => $itemSgst,
                    'total' => $itemTotal,
                    'sort_order' => $index,
                ]);
            }

            Log::info('Invoice created', ['invoice_id' => $invoice->id, 'total' => $total]);

            return redirect()->route('billing.show', $invoice)
                ->with('success', 'Invoice created successfully');
                
        } catch (\Throwable $e) {
            Log::error('Invoice creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    public function show(Invoice $invoice)
    {
        Log::info('BillingWebController@show', ['invoice' => $invoice->id]);

        $invoice->load(['patient', 'items', 'payments', 'clinic']);

        return view('billing.show', compact('invoice'));
    }

    public function pdf(Invoice $invoice)
    {
        Log::info('BillingWebController@pdf', ['invoice' => $invoice->id]);

        try {
            $invoice->load(['patient', 'items', 'clinic']);

            // Check if DomPDF is available
            if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                // Fallback: return HTML view for printing
                return view('billing.invoice-pdf', compact('invoice'));
            }

            $pdf = Pdf::loadView('billing.invoice-pdf', compact('invoice'));
            
            return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
        } catch (\Throwable $e) {
            Log::error('PDF generation failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            
            // Fallback: show printable HTML version
            $invoice->load(['patient', 'items', 'clinic']);
            return view('billing.invoice-pdf', compact('invoice'));
        }
    }

    public function sendWhatsApp(Invoice $invoice)
    {
        Log::info('BillingWebController@sendWhatsApp', ['invoice' => $invoice->id]);

        try {
            $invoice->load(['patient', 'clinic']);
            $patient = $invoice->patient;
            $clinic = $invoice->clinic;

            if (!$patient || !$patient->phone) {
                return back()->with('error', 'Patient phone number not available');
            }

            // Format phone number
            $phone = preg_replace('/[^0-9]/', '', $patient->phone);
            if (strlen($phone) === 10) {
                $phone = '91' . $phone;
            }

            // Create WhatsApp message
            $message = "Hello {$patient->name},\n\n";
            $message .= "Your invoice from {$clinic->name} is ready.\n\n";
            $message .= "Invoice #: {$invoice->invoice_number}\n";
            $message .= "Amount: ₹" . number_format($invoice->total, 2) . "\n";
            $message .= "Status: " . ucfirst($invoice->payment_status) . "\n\n";
            
            $balance = $invoice->total - ($invoice->paid ?? 0);
            if ($balance > 0) {
                $message .= "Balance Due: ₹" . number_format($balance, 2) . "\n\n";
            }
            
            $message .= "Thank you for choosing {$clinic->name}!";

            // Generate WhatsApp URL
            $whatsappUrl = "https://wa.me/{$phone}?text=" . urlencode($message);

            // Update invoice
            $invoice->update(['whatsapp_link_sent_at' => now()]);

            // Redirect to WhatsApp (opens in new tab via JavaScript)
            return back()->with('success', 'Opening WhatsApp...')
                         ->with('whatsapp_url', $whatsappUrl);
                         
        } catch (\Throwable $e) {
            Log::error('WhatsApp send failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to send WhatsApp: ' . $e->getMessage());
        }
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        Log::info('BillingWebController@markPaid', ['invoice' => $invoice->id]);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'method' => 'required|in:cash,upi,card,bank_transfer,netbanking',
            'reference' => 'nullable|string',
        ]);

        try {
            Payment::create([
                'clinic_id' => $invoice->clinic_id,
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['method'],
                'transaction_ref' => $validated['reference'] ?? null,
                'payment_date' => now(),
                'recorded_by' => auth()->id(),
            ]);

            // Update invoice paid amount and status
            $invoice->paid = ($invoice->paid ?? 0) + $validated['amount'];
            if ($invoice->paid >= $invoice->total) {
                $invoice->payment_status = 'paid';
            } elseif ($invoice->paid > 0) {
                $invoice->payment_status = 'partial';
            }
            $invoice->save();

            Log::info('Payment recorded successfully', ['invoice_id' => $invoice->id, 'amount' => $validated['amount']]);

            return back()->with('success', 'Payment recorded successfully');
        } catch (\Throwable $e) {
            Log::error('Payment recording failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }
}
