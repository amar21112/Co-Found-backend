<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notification_preferences';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'platform_notifications',
        'email_notifications',
        'push_notifications',
        'notification_digest',
        'quiet_hours_start',
        'quiet_hours_end',
        'quiet_hours_timezone',
        'preferences'
    ];

    protected $casts = [
        'platform_notifications' => 'boolean',
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'quiet_hours_start' => 'datetime:H:i',
        'quiet_hours_end' => 'datetime:H:i',
        'preferences' => 'array'
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shouldNotify($type, $channel = 'platform')
    {
        // Check master switch
        if ($channel === 'platform' && !$this->platform_notifications) {
            return false;
        }

        if ($channel === 'email' && !$this->email_notifications) {
            return false;
        }

        if ($channel === 'push' && !$this->push_notifications) {
            return false;
        }

        // Check quiet hours
        if ($this->quiet_hours_start && $this->quiet_hours_end) {
            $now = now()->setTimezone($this->quiet_hours_timezone ?? config('app.timezone'));
            $start = now()->setTimezone($this->quiet_hours_timezone ?? config('app.timezone'))
                ->setTimeFromTimeString($this->quiet_hours_start);
            $end = now()->setTimezone($this->quiet_hours_timezone ?? config('app.timezone'))
                ->setTimeFromTimeString($this->quiet_hours_end);

            if ($now->between($start, $end)) {
                return false;
            }
        }

        // Check specific preference
        if (isset($this->preferences[$type])) {
            return $this->preferences[$type] !== false;
        }

        return true;
    }

    public function setPreference($type, $enabled)
    {
        $preferences = $this->preferences;
        $preferences[$type] = $enabled;
        $this->preferences = $preferences;
        $this->save();
    }
}
