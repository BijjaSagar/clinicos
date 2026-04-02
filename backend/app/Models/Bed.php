<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

class Bed extends Model
{
    protected $table = 'hospital_beds';

    protected $fillable = [
        'clinic_id',
        'room_id',
        'bed_code',
        'status',
        'gender_restriction',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (Bed $bed) {
            Log::info('Creating bed', [
                'clinic_id' => $bed->clinic_id,
                'room_id'   => $bed->room_id,
                'bed_code'  => $bed->bed_code,
            ]);
        });

        static::updating(function (Bed $bed) {
            if ($bed->isDirty('status')) {
                Log::info('Bed status changed', [
                    'id'         => $bed->id,
                    'bed_code'   => $bed->bed_code,
                    'old_status' => $bed->getOriginal('status'),
                    'new_status' => $bed->status,
                ]);
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function room(): BelongsTo
    {
        return $this->belongsTo(HospitalRoom::class, 'room_id');
    }

    // Access ward through room
    public function ward(): BelongsTo
    {
        return $this->room->ward ?? null;
    }

    public function currentAdmission(): HasOne
    {
        return $this->hasOne(IpdAdmission::class)->where('status', 'admitted');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isOccupied(): bool
    {
        return $this->status === 'occupied';
    }

    // Compatibility accessor — old code uses bed_number
    public function getBedNumberAttribute(): ?string
    {
        return $this->bed_code;
    }

    // Compatibility: old code checks bed->ward_id
    public function getWardIdAttribute(): ?int
    {
        return $this->room?->ward_id;
    }

    public function getStatusColorClass(): string
    {
        return match ($this->status) {
            'available'   => 'bg-green-100 text-green-700 border-green-200',
            'occupied'    => 'bg-red-100 text-red-700 border-red-200',
            'cleaning'    => 'bg-yellow-100 text-yellow-700 border-yellow-200',
            'maintenance' => 'bg-gray-100 text-gray-600 border-gray-200',
            default       => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    }
}
