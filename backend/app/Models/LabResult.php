<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    protected $fillable = [
        'lab_order_item_id', 'clinic_id', 'result_value', 'result_unit',
        'reference_range', 'is_abnormal', 'is_critical', 'remarks',
        'entered_by', 'verified_by', 'verified_at', 'status',
    ];

    protected $casts = [
        'is_abnormal' => 'boolean',
        'is_critical' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(LabOrderTest::class, 'lab_order_item_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
