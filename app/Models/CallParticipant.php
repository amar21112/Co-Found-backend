<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallParticipant extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'call_participants';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'call_id',
        'user_id',
        'joined_at',
        'left_at',
        'duration_seconds',
        'role'
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'duration_seconds' => 'integer'
    ];

    public $timestamps = false;

    public function call()
    {
        return $this->belongsTo(VideoCall::class, 'call_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePresent($query)
    {
        return $query->whereNull('left_at');
    }

    public function scopeLeft($query)
    {
        return $query->whereNotNull('left_at');
    }

    public function scopeHosts($query)
    {
        return $query->where('role', 'host');
    }

    public function isPresent()
    {
        return is_null($this->left_at);
    }

    public function join()
    {
        $this->joined_at = now();
        $this->save();
    }

    public function leave()
    {
        $this->left_at = now();

        if ($this->joined_at) {
            $this->duration_seconds = $this->joined_at->diffInSeconds($this->left_at);
        }

        $this->save();
    }

    public function getDurationFormattedAttribute()
    {
        if (!$this->duration_seconds) {
            return null;
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
