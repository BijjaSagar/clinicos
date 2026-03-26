<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class AppointmentService extends Model
{
    protected $table = 'appointment_services';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'name',
        'specialty',
        'duration_mins',
        'advance_amount',
        'color_hex',
        'is_active',
        'requires_room',
        'requires_equipment',
        'pre_visit_questions',
    ];

    protected $casts = [
        'duration_mins' => 'integer',
        'advance_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'requires_room' => 'boolean',
        'requires_equipment' => 'boolean',
        'pre_visit_questions' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AppointmentService $service) {
            Log::info('Creating appointment service', [
                'clinic_id' => $service->clinic_id,
                'name' => $service->name,
                'duration_mins' => $service->duration_mins
            ]);
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'service_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySpecialty($query, string $specialty)
    {
        return $query->where('specialty', $specialty);
    }

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }
}
