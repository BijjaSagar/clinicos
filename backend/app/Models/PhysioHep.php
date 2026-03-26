<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class PhysioHep extends Model
{
    protected $table = 'physio_hep';

    public $timestamps = false;

    protected $fillable = [
        'visit_id',
        'patient_id',
        'exercise_name',
        'sets',
        'reps',
        'hold_seconds',
        'frequency_per_day',
        'instructions',
        'image_url',
        'video_url',
        'whatsapp_sent_at',
    ];

    protected $casts = [
        'sets' => 'integer',
        'reps' => 'integer',
        'hold_seconds' => 'integer',
        'frequency_per_day' => 'integer',
        'whatsapp_sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PhysioHep $hep) {
            Log::info('Creating HEP exercise', [
                'visit_id' => $hep->visit_id,
                'exercise_name' => $hep->exercise_name,
                'sets' => $hep->sets,
                'reps' => $hep->reps
            ]);
        });
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopeForVisit($query, int $visitId)
    {
        return $query->where('visit_id', $visitId);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeNotSent($query)
    {
        return $query->whereNull('whatsapp_sent_at');
    }

    public function wasSentViaWhatsapp(): bool
    {
        return !is_null($this->whatsapp_sent_at);
    }

    public function markAsSent(): void
    {
        $this->update(['whatsapp_sent_at' => now()]);
        Log::info('HEP marked as sent via WhatsApp', ['id' => $this->id]);
    }

    public function getExerciseDescription(): string
    {
        $parts = [];

        if ($this->sets && $this->reps) {
            $parts[] = "{$this->sets} sets x {$this->reps} reps";
        }

        if ($this->hold_seconds) {
            $parts[] = "Hold for {$this->hold_seconds} seconds";
        }

        if ($this->frequency_per_day) {
            $parts[] = "{$this->frequency_per_day}x daily";
        }

        return implode(', ', $parts);
    }

    public function toWhatsappMessage(): string
    {
        $message = "*{$this->exercise_name}*\n";
        $message .= $this->getExerciseDescription() . "\n";
        
        if ($this->instructions) {
            $message .= "\n_{$this->instructions}_";
        }

        return $message;
    }
}
