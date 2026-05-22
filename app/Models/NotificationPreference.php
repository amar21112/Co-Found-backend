<?php

namespace App\Models;

use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property bool $platform_notifications
 * @property bool $email_notifications
 * @property bool $push_notifications
 * @property string|null $notification_digest
 * @property string|null $quiet_hours_start
 * @property string|null $quiet_hours_end
 * @property string|null $quiet_hours_timezone
 * @property array|null $preferences
 * @property Carbon|null $updated_at
 *
 * @method static Builder|NotificationPreference create(array $attributes = [])
 * @method static Builder|NotificationPreference find($id, $columns = ['*'])
 * @method static Builder|NotificationPreference findOrFail($id, $columns = ['*'])
 * @method static Builder|NotificationPreference first($columns = ['*'])
 * @method static Builder|NotificationPreference firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|NotificationPreference firstOrFail($columns = ['*'])
 * @method static Builder|NotificationPreference newModelQuery()
 * @method static Builder|NotificationPreference newQuery()
 * @method static Builder|NotificationPreference query()
 * @method static Builder|NotificationPreference where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|NotificationPreference whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|NotificationPreference with($relations, $callback = null)
 *
 * @method static Builder|NotificationPreference whereId($value)
 * @method static Builder|NotificationPreference whereUserId($value)
 * @method static Builder|NotificationPreference wherePlatformNotifications($value)
 * @method static Builder|NotificationPreference whereEmailNotifications($value)
 * @method static Builder|NotificationPreference wherePushNotifications($value)
 * @method static Builder|NotificationPreference whereNotificationDigest($value)
 * @method static Builder|NotificationPreference whereQuietHoursStart($value)
 * @method static Builder|NotificationPreference whereQuietHoursEnd($value)
 * @method static Builder|NotificationPreference whereQuietHoursTimezone($value)
 * @method static Builder|NotificationPreference wherePreferences($value)
 * @method static Builder|NotificationPreference whereUpdatedAt($value)
 *
 * @property-read User $user
 *
 * @method static NotificationPreferenceFactory factory($count = null, $state = [])
 */
class NotificationPreference extends Model
{
    use HasUuids, HasFactory;

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
