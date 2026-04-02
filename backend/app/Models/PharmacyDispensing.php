<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyDispensing extends Model
{
    protected $table = 'pharmacy_dispensing';

    /**
     * Allow mass-assignment on all columns — the controller validates before
     * creating, so there are no sensitive auto-fields to guard here.
     */
    protected $guarded = [];

    protected $casts = [
        'dispensed_at'    => 'datetime',
        'total_amount'    => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_amount'     => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PharmacyDispensingItem::class, 'dispensing_id');
    }

    /**
     * The staff member who processed the dispensing.
     */
    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }
}
