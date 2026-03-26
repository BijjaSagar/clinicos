<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class LabOrder extends Model
{
    protected $table = 'lab_orders';

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'doctor_id',
        'visit_id',
        'vendor_id',
        'order_number',
        'is_urgent',
        'status',
        'result_pdf_url',
        'result_pdf_s3_key',
        'result_sent_at',
        'result_sent_to_patient',
        'fhir_resource_id',
        'total_amount',
        'clinical_notes',
    ];

    protected $casts = [
        'is_urgent' => 'boolean',
        'result_sent_at' => 'datetime',
        'result_sent_to_patient' => 'boolean',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_NEW = 'new';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_SAMPLE_COLLECTED = 'sample_collected';
    const STATUS_PROCESSING = 'processing';
    const STATUS_READY = 'ready';
    const STATUS_SENT = 'sent';
    const STATUS_CANCELLED = 'cancelled';

    protected static function booted(): void
    {
        static::creating(function (LabOrder $order) {
            Log::info('Creating lab order', [
                'clinic_id' => $order->clinic_id,
                'patient_id' => $order->patient_id,
                'vendor_id' => $order->vendor_id
            ]);

            // Auto-generate order number
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });

        static::created(function (LabOrder $order) {
            Log::info('Lab order created', ['id' => $order->id, 'order_number' => $order->order_number]);
        });

        static::updating(function (LabOrder $order) {
            if ($order->isDirty('status')) {
                Log::info('Lab order status changed', [
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

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorLab::class, 'vendor_id');
    }

    public function tests(): HasMany
    {
        return $this->hasMany(LabOrderTest::class);
    }

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopePending($query)
    {
        return $query->whereNotIn('status', [self::STATUS_SENT, self::STATUS_CANCELLED]);
    }

    public function scopeReady($query)
    {
        return $query->where('status', self::STATUS_READY);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function hasResults(): bool
    {
        return !empty($this->result_pdf_url);
    }

    public function wasSentToPatient(): bool
    {
        return $this->result_sent_to_patient === true;
    }

    public static function generateOrderNumber(): string
    {
        $year = now()->year;
        $lastOrder = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastOrder && preg_match('/(\d+)$/', $lastOrder->order_number, $matches)) {
            $sequence = (int)$matches[1] + 1;
        }

        return sprintf('LO-%d-%06d', $year, $sequence);
    }
}
