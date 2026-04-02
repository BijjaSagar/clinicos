<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpdMedicationOrder extends Model
{
    protected $table = 'ipd_medication_orders';

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_sos'     => 'boolean',
        'stopped_at' => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id');
    }

    public function prescribedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prescribed_by');
    }

    // Alias so views using ->orderedBy still work
    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prescribed_by');
    }

    public function stoppedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stopped_by');
    }
}
