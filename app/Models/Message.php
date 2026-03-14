<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'conversation_id', 'sender_id', 'message_type',
        'content', 'formatted_content', 'replied_to_message_id',
        'is_pinned', 'is_edited',
    ];

    protected $casts = [
        'formatted_content' => 'array',
        'is_pinned'         => 'boolean',
        'is_edited'         => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** The message this is a reply to */
    public function repliedTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'replied_to_message_id');
    }

    /** Replies to this message */
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'replied_to_message_id');
    }

    public function readReceipts(): HasMany
    {
        return $this->hasMany(MessageReadReceipt::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function sharedFiles(): HasMany
    {
        return $this->hasMany(SharedFile::class);
    }
}
