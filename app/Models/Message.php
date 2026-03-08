<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'messages';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message_type',
        'content',
        'formatted_content',
        'replied_to_message_id',
        'is_pinned',
        'is_edited'
    ];

    protected $casts = [
        'formatted_content' => 'array',
        'is_pinned' => 'boolean',
        'is_edited' => 'boolean'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function repliedTo()
    {
        return $this->belongsTo(Message::class, 'replied_to_message_id');
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'replied_to_message_id');
    }

    public function readReceipts()
    {
        return $this->hasMany(MessageReadReceipt::class, 'message_id');
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class, 'message_id');
    }

    public function sharedFiles()
    {
        return $this->hasMany(SharedFile::class, 'message_id');
    }

    public function scopeInConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeFromSender($query, $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function markAsRead($userId)
    {
        return MessageReadReceipt::firstOrCreate([
            'message_id' => $this->id,
            'user_id' => $userId
        ]);
    }

    public function isReadBy($userId)
    {
        return $this->readReceipts()
            ->where('user_id', $userId)
            ->exists();
    }

    public function getReadCountAttribute()
    {
        return $this->readReceipts()->count();
    }

    public function getReactionSummaryAttribute()
    {
        return $this->reactions()
            ->selectRaw('reaction, count(*) as count')
            ->groupBy('reaction')
            ->pluck('count', 'reaction')
            ->toArray();
    }

    public function edit($newContent)
    {
        $this->content = $newContent;
        $this->is_edited = true;
        $this->updated_at = now();
        $this->save();
    }

    public function pin()
    {
        $this->is_pinned = true;
        $this->save();
    }

    public function unpin()
    {
        $this->is_pinned = false;
        $this->save();
    }
}
