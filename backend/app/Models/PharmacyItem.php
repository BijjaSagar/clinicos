<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PharmacyItem extends Model
{
    protected $table = 'pharmacy_items';

    protected $fillable = [
        'clinic_id',
        'name',
        'generic_name',
        'drug_id',
        'category_id',
        'hsn_code',
        'unit',
        'pack_size',
        'manufacturer',
        'schedule',
        'is_controlled',
        'gst_rate',
        'mrp',
        'selling_price',
        'reorder_level',
        'reorder_qty',
        'storage_conditions',
        'is_active',
    ];

    protected $casts = [
        'is_controlled'  => 'boolean',
        'is_active'      => 'boolean',
        'gst_rate'       => 'decimal:2',
        'mrp'            => 'decimal:2',
        'selling_price'  => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function stocks(): HasMany
    {
        return $this->hasMany(PharmacyStock::class, 'item_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Items whose current stock is at or below their reorder level.
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw(
            '(SELECT COALESCE(SUM(quantity_available), 0) FROM pharmacy_stock WHERE pharmacy_stock.item_id = pharmacy_items.id) <= pharmacy_items.reorder_level'
        );
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    /**
     * Real-time stock quantity derived from pharmacy_stock.
     */
    public function getStockQuantityAttribute(): int
    {
        return (int) DB::table('pharmacy_stock')
            ->where('item_id', $this->id)
            ->sum('quantity_available');
    }
}
