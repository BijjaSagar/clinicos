<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'visit_id',
        'invoice_number',
        'invoice_date',
        'subtotal',
        'discount_amount',
        'discount_pct',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total',
        'advance_adjusted',
        'paid',
        'payment_status',
        'place_of_supply',
        'reverse_charge',
        'irn',
        'ack_number',
        'irn_generated_at',
        'is_insurance_claim',
        'insurer_name',
        'claim_id',
        'tpa_name',
        'pdf_url',
        'whatsapp_link_sent_at',
        'email_sent_at',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_pct' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'advance_adjusted' => 'decimal:2',
        'paid' => 'decimal:2',
        'reverse_charge' => 'boolean',
        'is_insurance_claim' => 'boolean',
        'irn_generated_at' => 'datetime',
        'whatsapp_link_sent_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID = 'paid';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_VOID = 'void';

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            Log::info('Creating invoice', [
                'clinic_id' => $invoice->clinic_id,
                'patient_id' => $invoice->patient_id,
                'invoice_number' => $invoice->invoice_number,
                'total' => $invoice->total
            ]);

            // Auto-generate invoice number if not set
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber($invoice->clinic_id);
            }
        });

        static::created(function (Invoice $invoice) {
            Log::info('Invoice created', ['id' => $invoice->id, 'invoice_number' => $invoice->invoice_number]);
        });

        static::updating(function (Invoice $invoice) {
            Log::info('Updating invoice', ['id' => $invoice->id, 'changes' => $invoice->getDirty()]);
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::STATUS_PAID);
    }

    public function scopeOutstanding($query)
    {
        return $query->whereIn('payment_status', [self::STATUS_PENDING, self::STATUS_PARTIAL]);
    }

    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->whereMonth('invoice_date', $month)
                     ->whereYear('invoice_date', $year);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function getBalanceDue(): float
    {
        return max(0, $this->total - $this->paid);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::STATUS_PAID;
    }

    public function isOverdue(): bool
    {
        return $this->getBalanceDue() > 0 && $this->invoice_date->diffInDays(now()) > 30;
    }

    public function recordPayment(float $amount, string $method, ?string $razorpayPaymentId = null): Payment
    {
        $payment = $this->payments()->create([
            'clinic_id' => $this->clinic_id,
            'patient_id' => $this->patient_id,
            'amount' => $amount,
            'payment_method' => $method,
            'razorpay_payment_id' => $razorpayPaymentId,
            'payment_date' => now(),
        ]);

        $this->paid += $amount;
        
        if ($this->paid >= $this->total) {
            $this->payment_status = self::STATUS_PAID;
        } elseif ($this->paid > 0) {
            $this->payment_status = self::STATUS_PARTIAL;
        }
        
        $this->save();

        Log::info('Payment recorded', [
            'invoice_id' => $this->id,
            'amount' => $amount,
            'new_status' => $this->payment_status
        ]);

        return $payment;
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum('taxable_amount');
        $cgst = $this->items->sum('cgst_amount');
        $sgst = $this->items->sum('sgst_amount');
        
        $this->subtotal = $subtotal;
        $this->cgst_amount = $cgst;
        $this->sgst_amount = $sgst;
        $this->total = $subtotal + $cgst + $sgst - $this->discount_amount;
        
        Log::info('Invoice totals calculated', [
            'invoice_id' => $this->id,
            'subtotal' => $this->subtotal,
            'total' => $this->total
        ]);
    }

    public static function generateInvoiceNumber(int $clinicId): string
    {
        $year = now()->year;
        $prefix = sprintf('CLN%03d', $clinicId);
        
        $lastInvoice = self::where('clinic_id', $clinicId)
            ->whereYear('invoice_date', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = 1;
        if ($lastInvoice && preg_match('/(\d+)$/', $lastInvoice->invoice_number, $matches)) {
            $sequence = (int)$matches[1] + 1;
        }
        
        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }

    public function getTotalGst(): float
    {
        return $this->cgst_amount + $this->sgst_amount + $this->igst_amount;
    }

    public function isIntraState(): bool
    {
        return $this->igst_amount == 0;
    }
}
