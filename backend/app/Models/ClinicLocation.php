<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class ClinicLocation extends Model
{
    protected $table = 'clinic_locations';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'name',
        'address',
        'phone',
        'is_primary',
        'is_active',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicLocation $location) {
            Log::info('Creating clinic location', ['clinic_id' => $location->clinic_id, 'name' => $location->name]);
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
