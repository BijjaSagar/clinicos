<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyStock extends Model
{
    protected $table = 'pharmacy_stock';

    protected $fillable = [
        'clinic_id',
        'item_id',
        'batch_number',
        'expiry_date',
        'quantity_in',
        'quantity_out',
        'quantity_available',
        'purchase_rate',
        'mrp',
        'supplier_id',
        'grn_id',
    ];

    protected $casts = [
        'expiry_date'        => 'date',
        'quantity_in'        => 'integer',
        'quantity_out'       => 'integer',
        'quantity_available' => 'integer',
        'purchase_rate'      => 'decimal:2',
        'mrp'                => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function item(): BelongsTo
    {
        return $this->belongsTo(PharmacyItem::class, 'item_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Batches that have not yet expired.
     */
    public function scopeNonExpired($query)
    {
        return $query->where('expiry_date', '>=', now()->toDateString());
    }

    /**
     * Batches expiring within the next 90 days.
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('expiry_date', '>=', now()->toDateString())
                     ->where('expiry_date', '<=', now()->addDays(90)->toDateString());
    }
}
