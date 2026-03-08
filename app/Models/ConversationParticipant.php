<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationParticipant extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'conversation_participants';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'joined_at',
        'left_at',
        'is_admin',
        'muted',
        'muted_until'
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'is_admin' => 'boolean',
        'muted' => 'boolean',
        'muted_until' => 'datetime'
    ];

    public $timestamps = false;

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('left_at');
    }

    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    public function scopeMuted($query)
    {
        return $query->where('muted', true);
    }

    public function isActive()
    {
        return is_null($this->left_at);
    }

    public function isMuted()
    {
        if (!$this->muted) {
            return false;
        }

        if ($this->muted_until && $this->muted_until < now()) {
            return false;
        }

        return true;
    }

    public function mute($until = null)
    {
        $this->muted = true;
        $this->muted_until = $until;
        $this->save();
    }

    public function unmute()
    {
        $this->muted = false;
        $this->muted_until = null;
        $this->save();
    }
}
