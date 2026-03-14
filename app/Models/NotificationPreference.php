<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'user_id', 'platform_notifications', 'email_notifications',
        'push_notifications', 'notification_digest',
        'quiet_hours_start', 'quiet_hours_end', 'quiet_hours_timezone',
        'preferences',
    ];

    protected $casts = [
        'platform_notifications' => 'boolean',
        'email_notifications'    => 'boolean',
        'push_notifications'     => 'boolean',
        'preferences'            => 'array',
        'updated_at'             => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
