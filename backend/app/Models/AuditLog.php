<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForEntity($query, string $entityType, ?int $entityId = null)
    {
        $query->where('entity_type', $entityType);
        
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }

        return $query;
    }

    public static function log(
        int $clinicId,
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        $log = self::create([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Log::info('Audit log created', [
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ]);

        return $log;
    }
}
