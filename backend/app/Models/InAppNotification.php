<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class InAppNotification extends Model
{
    protected $fillable = [
        'user_id', 'clinic_id', 'type', 'title', 'body',
        'action_url', 'icon', 'colour', 'is_read', 'read_at',
    ];

    protected $casts = [
        'is_read'  => 'boolean',
        'read_at'  => 'datetime',
    ];

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function send(
        int    $userId,
        int    $clinicId,
        string $type,
        string $title,
        string $body,
        string $actionUrl = null,
        string $icon      = 'bell',
        string $colour    = 'blue'
    ): self {
        $notif = static::create([
            'user_id'    => $userId,
            'clinic_id'  => $clinicId,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'action_url' => $actionUrl,
            'icon'       => $icon,
            'colour'     => $colour,
        ]);

        Log::info('InAppNotification sent', [
            'id'      => $notif->id,
            'user_id' => $userId,
            'type'    => $type,
        ]);

        return $notif;
    }

    public static function unreadCountFor(int $userId): int
    {
        return static::where('user_id', $userId)->where('is_read', false)->count();
    }

    public function markRead(): void
    {
        $this->update(['is_read' => true, 'read_at' => now()]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
