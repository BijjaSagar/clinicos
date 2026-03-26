<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class InvoiceItem extends Model
{
    protected $table = 'invoice_items';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'description',
        'item_type',
        'sac_code',
        'hsn_code',
        'gst_rate',
        'unit_price',
        'quantity',
        'discount_pct',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'total',
        'sort_order',
    ];

    protected $casts = [
        'gst_rate' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'discount_pct' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    const TYPE_SERVICE = 'service';
    const TYPE_PROCEDURE = 'procedure';
    const TYPE_PRODUCT = 'product';
    const TYPE_CONSULTATION = 'consultation';
    const TYPE_PACKAGE = 'package';

    // Common GST SAC codes for medical services
    const SAC_CLINICAL_CONSULTATION = '999311';  // 0% GST - exempt
    const SAC_COSMETIC_PROCEDURE = '999312';     // 18% GST
    const SAC_PHYSIOTHERAPY = '999321';          // 0% GST - exempt
    const SAC_HEALTH_PACKAGE = '999713';         // 18% GST

    protected static function booted(): void
    {
        static::creating(function (InvoiceItem $item) {
            Log::info('Creating invoice item', [
                'invoice_id' => $item->invoice_id,
                'description' => $item->description,
                'unit_price' => $item->unit_price
            ]);

            // Calculate amounts if not set
            if (!$item->taxable_amount) {
                $item->calculateAmounts();
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function calculateAmounts(): void
    {
        $baseAmount = $this->unit_price * $this->quantity;
        $discountAmount = $baseAmount * ($this->discount_pct / 100);
        $this->taxable_amount = $baseAmount - $discountAmount;
        
        // Calculate GST (split into CGST + SGST for intra-state)
        $gstAmount = $this->taxable_amount * ($this->gst_rate / 100);
        $this->cgst_amount = $gstAmount / 2;
        $this->sgst_amount = $gstAmount / 2;
        
        $this->total = $this->taxable_amount + $gstAmount;

        Log::debug('Invoice item amounts calculated', [
            'taxable_amount' => $this->taxable_amount,
            'gst_rate' => $this->gst_rate,
            'cgst' => $this->cgst_amount,
            'sgst' => $this->sgst_amount,
            'total' => $this->total
        ]);
    }

    public function isExempt(): bool
    {
        return $this->gst_rate == 0;
    }

    public static function getGstRateForSac(string $sacCode): float
    {
        $rates = [
            '999311' => 0.00,   // Clinical consultation - exempt
            '999312' => 18.00,  // Cosmetic procedures
            '999321' => 0.00,   // Physiotherapy - exempt
            '9993' => 0.00,     // General health services - exempt
            '999713' => 18.00,  // Health check packages
        ];

        return $rates[$sacCode] ?? 18.00;
    }
}
