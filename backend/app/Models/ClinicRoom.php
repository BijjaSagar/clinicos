<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class ClinicRoom extends Model
{
    protected $table = 'clinic_rooms';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'location_id',
        'name',
        'room_type',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicRoom $room) {
            Log::info('Creating clinic room', ['clinic_id' => $room->clinic_id, 'name' => $room->name]);
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ClinicLocation::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'room_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('room_type', $type);
    }
}
