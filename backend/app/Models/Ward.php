<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Ward extends Model
{
    protected $table = 'wards';

    protected $fillable = [
        'clinic_id',
        'name',
        'ward_type',
        'floor',
        'total_beds',
        'is_active',
    ];

    protected $casts = [
        'total_beds' => 'integer',
        'is_active'  => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ward $ward) {
            Log::info('Creating ward', ['clinic_id' => $ward->clinic_id, 'name' => $ward->name]);
        });

        static::created(function (Ward $ward) {
            Log::info('Ward created', ['id' => $ward->id, 'name' => $ward->name]);
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(ClinicRoom::class);
    }

    public function ipdAdmissions(): HasMany
    {
        return $this->hasMany(IpdAdmission::class);
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

    public function getOccupancyPercentage(): float
    {
        if ($this->total_beds === 0) {
            return 0;
        }

        $occupied = $this->beds()->where('status', 'occupied')->count();

        return round(($occupied / $this->total_beds) * 100, 1);
    }
}
