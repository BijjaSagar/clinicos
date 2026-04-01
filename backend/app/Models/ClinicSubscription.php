<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicSubscription extends Model
{
    protected $table = 'clinic_subscriptions';

    protected $fillable = [
        'clinic_id',
        'plan',
        'status',
        'billing_cycle',
        'amount',
        'currency',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'razorpay_subscription_id',
        'razorpay_plan_id',
        'razorpay_customer_id',
        'next_billing_date',
        'auto_renew',
        'notes',
    ];

    protected $casts = [
        'trial_ends_at'        => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end'   => 'datetime',
        'cancelled_at'         => 'datetime',
        'next_billing_date'    => 'date',
        'amount'               => 'decimal:2',
        'auto_renew'           => 'boolean',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    // ─── Status helpers ───────────────────────────────────────────────────────

    /**
     * Returns true when the subscription is actively billable:
     * either in a paid active state, or still within the trial window.
     */
    public function isActive(): bool
    {
        if ($this->status === 'active') {
            return true;
        }

        if ($this->status === 'trial' && $this->trial_ends_at !== null) {
            return $this->trial_ends_at->isFuture();
        }

        return false;
    }

    /**
     * Returns true when the subscription has expired:
     * status is 'expired', or trial has ended without converting.
     */
    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->status === 'trial' && $this->trial_ends_at !== null) {
            return $this->trial_ends_at->isPast();
        }

        return false;
    }

    /**
     * Returns the number of whole days remaining until the current period ends.
     * Returns 0 if current_period_end is not set or already past.
     */
    public function daysUntilRenewal(): int
    {
        if ($this->current_period_end === null) {
            return 0;
        }

        $diff = (int) now()->diffInDays($this->current_period_end, false);

        return max(0, $diff);
    }
}
