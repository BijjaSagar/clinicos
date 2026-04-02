<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HospitalRoom extends Model
{
    protected $table = 'hospital_rooms';

    protected $fillable = [
        'clinic_id',
        'ward_id',
        'name',
        'room_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class, 'room_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
