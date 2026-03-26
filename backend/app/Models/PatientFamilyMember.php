<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class PatientFamilyMember extends Model
{
    protected $table = 'patient_family_members';

    public $timestamps = false;

    protected $fillable = [
        'patient_id',
        'clinic_id',
        'name',
        'relation',
        'phone',
        'linked_patient_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientFamilyMember $member) {
            Log::info('Creating patient family member', [
                'patient_id' => $member->patient_id,
                'name' => $member->name,
                'relation' => $member->relation
            ]);
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function linkedPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'linked_patient_id');
    }
}
