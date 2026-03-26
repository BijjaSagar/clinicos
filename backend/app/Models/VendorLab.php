<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;

class VendorLab extends Model
{
    protected $table = 'vendor_labs';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'lab_chain',
        'city',
        'contact_phone',
        'contact_email',
        'api_enabled',
        'api_endpoint',
        'is_active',
    ];

    protected $casts = [
        'api_enabled' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (VendorLab $vendor) {
            Log::info('Creating vendor lab', [
                'name' => $vendor->name,
                'city' => $vendor->city
            ]);
        });
    }

    public function testCatalog(): HasMany
    {
        return $this->hasMany(LabTestCatalog::class, 'vendor_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(LabOrder::class, 'vendor_id');
    }

    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'clinic_vendor_links', 'vendor_id', 'clinic_id')
                    ->withPivot(['discount_pct', 'is_preferred', 'linked_at']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeApiEnabled($query)
    {
        return $query->where('api_enabled', true);
    }

    public function isApiEnabled(): bool
    {
        return $this->api_enabled === true;
    }
}
