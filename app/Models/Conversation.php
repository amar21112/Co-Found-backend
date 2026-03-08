<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'conversations';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'conversation_type',
        'title',
        'project_id',
        'created_by',
        'last_message_at'
    ];

    protected $casts = [
        'last_message_at' => 'datetime'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class, 'conversation_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    public function sharedFiles()
    {
        return $this->hasMany(SharedFile::class, 'conversation_id');
    }

    public function videoCalls()
    {
        return $this->hasMany(VideoCall::class, 'conversation_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_participants', 'conversation_id', 'user_id')
            ->withPivot('joined_at', 'left_at', 'is_admin', 'muted', 'muted_until')
            ->withTimestamps();
    }

    public function scopeDirect($query)
    {
        return $query->where('conversation_type', 'direct');
    }

    public function scopeGroup($query)
    {
        return $query->where('conversation_type', 'group');
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function addParticipant($userId, $isAdmin = false)
    {
        return $this->participants()->create([
            'user_id' => $userId,
            'is_admin' => $isAdmin,
            'joined_at' => now()
        ]);
    }

    public function removeParticipant($userId)
    {
        $participant = $this->participants()
            ->where('user_id', $userId)
            ->first();

        if ($participant) {
            $participant->left_at = now();
            $participant->save();
        }
    }

    public function updateLastMessage()
    {
        $lastMessage = $this->messages()->latest()->first();
        $this->last_message_at = $lastMessage ? $lastMessage->created_at : null;
        $this->save();
    }

    public function getParticipantCountAttribute()
    {
        return $this->participants()->count();
    }
}
