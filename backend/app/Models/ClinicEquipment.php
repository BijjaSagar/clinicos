<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class ClinicEquipment extends Model
{
    protected $table = 'clinic_equipment';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'name',
        'equipment_type',
        'serial_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicEquipment $equipment) {
            Log::info('Creating clinic equipment', ['clinic_id' => $equipment->clinic_id, 'name' => $equipment->name]);
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'equipment_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('equipment_type', $type);
    }
}
