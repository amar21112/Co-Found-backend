<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRestriction extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_restrictions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'restricted_by',
        'restriction_type',
        'reason',
        'duration_hours',
        'starts_at',
        'expires_at',
        'is_active',
        'lifted_by',
        'lifted_at'
    ];

    protected $casts = [
        'duration_hours' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'lifted_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function restrictedBy()
    {
        return $this->belongsTo(User::class, 'restricted_by');
    }

    public function liftedBy()
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('is_active', true)
            ->where('expires_at', '<=', now());
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeWarnings($query)
    {
        return $query->where('restriction_type', 'warning');
    }

    public function scopeSuspensions($query)
    {
        return $query->where('restriction_type', 'suspension');
    }

    public function scopeBans($query)
    {
        return $query->where('restriction_type', 'ban');
    }

    public function isActive()
    {
        return $this->is_active &&
            (!$this->expires_at || $this->expires_at > now());
    }

    public function isExpired()
    {
        return $this->is_active && $this->expires_at && $this->expires_at <= now();
    }

    public function lift($userId = null)
    {
        $this->is_active = false;
        $this->lifted_by = $userId;
        $this->lifted_at = now();
        $this->save();
    }

    public function getRemainingTimeAttribute()
    {
        if (!$this->isActive() || !$this->expires_at) {
            return null;
        }

        return now()->diffForHumans($this->expires_at, ['parts' => 2]);
    }
}
