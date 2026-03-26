<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class DoctorAvailability extends Model
{
    protected $table = 'doctor_availability';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'doctor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_duration_mins',
        'max_patients',
        'location_id',
        'is_active',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'slot_duration_mins' => 'integer',
        'max_patients' => 'integer',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (DoctorAvailability $availability) {
            Log::info('Creating doctor availability', [
                'doctor_id' => $availability->doctor_id,
                'day_of_week' => $availability->day_of_week,
                'start_time' => $availability->start_time,
                'end_time' => $availability->end_time
            ]);
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ClinicLocation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeEffectiveOn($query, $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
        });
    }

    public function getDayName(): string
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return $days[$this->day_of_week] ?? 'Unknown';
    }
}
