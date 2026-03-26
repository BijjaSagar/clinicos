<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class DentalLabOrder extends Model
{
    protected $table = 'dental_lab_orders';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'visit_id',
        'tooth_code',
        'work_type',
        'shade',
        'preparation_notes',
        'lab_vendor',
        'delivery_date',
        'status',
        'cost',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'cost' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    const STATUS_SENT = 'sent';
    const STATUS_RECEIVED = 'received';
    const STATUS_FITTED = 'fitted';
    const STATUS_REJECTED = 'rejected';

    protected static function booted(): void
    {
        static::creating(function (DentalLabOrder $order) {
            Log::info('Creating dental lab order', [
                'patient_id' => $order->patient_id,
                'tooth_code' => $order->tooth_code,
                'work_type' => $order->work_type
            ]);
        });

        static::updating(function (DentalLabOrder $order) {
            if ($order->isDirty('status')) {
                Log::info('Dental lab order status changed', [
                    'id' => $order->id,
                    'old_status' => $order->getOriginal('status'),
                    'new_status' => $order->status
                ]);
            }
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_RECEIVED]);
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_RECEIVED]);
    }

    public function isOverdue(): bool
    {
        return $this->delivery_date && $this->delivery_date->isPast() && $this->isPending();
    }
}
