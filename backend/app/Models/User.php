<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'clinic_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'specialty',
        'qualification',
        'registration_number',
        'hpr_id',
        'signature_url',
        'is_active',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            Log::info('Creating new user', ['email' => $user->email, 'role' => $user->role]);
        });

        static::created(function (User $user) {
            Log::info('User created successfully', ['id' => $user->id, 'email' => $user->email]);
        });

        static::updating(function (User $user) {
            Log::info('Updating user', ['id' => $user->id, 'changes' => $user->getDirty()]);
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctorAvailabilities(): HasMany
    {
        return $this->hasMany(DoctorAvailability::class, 'doctor_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'doctor_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'doctor_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDoctors($query)
    {
        return $query->where('role', 'doctor');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isReceptionist(): bool
    {
        return $this->role === 'receptionist';
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function hasHprId(): bool
    {
        return !empty($this->hpr_id);
    }

    public function getFullQualification(): string
    {
        $parts = array_filter([$this->name, $this->qualification]);
        return implode(', ', $parts);
    }
}
