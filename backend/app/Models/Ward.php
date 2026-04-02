<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Log;

class Ward extends Model
{
    protected $table = 'hospital_wards';

    protected $fillable = [
        'clinic_id',
        'name',
        'code',
        'wing',
        'floor',
        'is_icu',
        'isolation_type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_icu'     => 'boolean',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(HospitalRoom::class, 'ward_id');
    }

    // Beds through rooms
    public function beds(): HasManyThrough
    {
        return $this->hasManyThrough(Bed::class, HospitalRoom::class, 'ward_id', 'room_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function getAvailableBedCount(): int
    {
        return $this->beds()->where('status', 'available')->count();
    }

    // Compatibility: old code accesses ward->ward_type
    public function getWardTypeAttribute(): string
    {
        return $this->is_icu ? 'icu' : 'general';
    }

    // Compatibility: old code accesses ward->total_beds
    public function getTotalBedsAttribute(): int
    {
        return $this->beds()->count();
    }
}
