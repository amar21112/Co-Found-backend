<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    use HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'conversation_id', 'user_id',
        'joined_at', 'left_at', 'is_admin', 'muted', 'muted_until',
    ];

    protected $casts = [
        'joined_at'  => 'datetime',
        'left_at'    => 'datetime',
        'muted_until'=> 'datetime',
        'is_admin'   => 'boolean',
        'muted'      => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool { return $this->left_at === null; }
    public function isMuted(): bool  { return $this->muted && (!$this->muted_until || $this->muted_until->isFuture()); }
}
