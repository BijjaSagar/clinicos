<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpdMedicationOrder extends Model
{
    protected $table = 'ipd_medication_orders';

    protected $guarded = [];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'is_prn'        => 'boolean',
        'is_stat'       => 'boolean',
        'is_active'     => 'boolean',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_admission_id');
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }
}
