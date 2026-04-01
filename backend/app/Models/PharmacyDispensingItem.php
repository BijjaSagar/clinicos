<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyDispensingItem extends Model
{
    protected $table = 'pharmacy_dispensing_items';

    protected $fillable = [
        'dispensing_id',
        'item_id',
        'stock_id',
        'batch_number',
        'expiry_date',
        'quantity',
        'selling_price',
        'gst_rate',
        'gst_amount',
        'total',
        'instructions',
    ];

    protected $casts = [
        'expiry_date'   => 'date',
        'quantity'      => 'integer',
        'selling_price' => 'decimal:2',
        'gst_rate'      => 'decimal:2',
        'gst_amount'    => 'decimal:2',
        'total'         => 'decimal:2',
    ];

    public function dispensing(): BelongsTo
    {
        return $this->belongsTo(PharmacyDispensing::class, 'dispensing_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PharmacyItem::class, 'item_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(PharmacyStock::class, 'stock_id');
    }
}
