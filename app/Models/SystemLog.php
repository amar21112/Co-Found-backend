<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'system_logs';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'log_level',
        'component',
        'event_type',
        'message',
        'details',
        'ip_address',
        'user_id'
    ];

    protected $casts = [
        'details' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeDebug($query)
    {
        return $query->where('log_level', 'debug');
    }

    public function scopeInfo($query)
    {
        return $query->where('log_level', 'info');
    }

    public function scopeWarning($query)
    {
        return $query->where('log_level', 'warning');
    }

    public function scopeError($query)
    {
        return $query->where('log_level', 'error');
    }

    public function scopeCritical($query)
    {
        return $query->where('log_level', 'critical');
    }

    public function scopeByComponent($query, $component)
    {
        return $query->where('component', $component);
    }

    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeLastHour($query)
    {
        return $query->where('created_at', '>=', now()->subHour());
    }

    public function scopeLastDay($query)
    {
        return $query->where('created_at', '>=', now()->subDay());
    }

    public function getLevelBadgeAttribute()
    {
        $colors = [
            'debug' => 'secondary',
            'info' => 'info',
            'warning' => 'warning',
            'error' => 'danger',
            'critical' => 'dark'
        ];

        return $colors[$this->log_level] ?? 'secondary';
    }
}
