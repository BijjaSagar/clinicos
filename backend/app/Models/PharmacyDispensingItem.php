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
        'batch_number',
        'quantity',
        'unit_price',
        'gst_amount',
        'total_price',
        'instructions',
    ];

    protected $casts = [
        'quantity'    => 'integer',
        'unit_price'  => 'decimal:2',
        'gst_amount'  => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function dispensing(): BelongsTo
    {
        return $this->belongsTo(PharmacyDispensing::class, 'dispensing_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PharmacyItem::class, 'item_id');
    }
}
