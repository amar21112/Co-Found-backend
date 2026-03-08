<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoCall extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'video_calls';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'call_type',
        'conversation_id',
        'project_id',
        'initiated_by',
        'room_name',
        'room_url',
        'start_time',
        'end_time',
        'duration_seconds',
        'status',
        'recording_url'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_seconds' => 'integer'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function participants()
    {
        return $this->hasMany(CallParticipant::class, 'call_id');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeEnded($query)
    {
        return $query->where('status', 'ended');
    }

    public function scopeForConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function start()
    {
        $this->status = 'active';
        $this->start_time = now();
        $this->save();
    }

    public function end()
    {
        $this->status = 'ended';
        $this->end_time = now();

        if ($this->start_time) {
            $this->duration_seconds = $this->start_time->diffInSeconds($this->end_time);
        }

        $this->save();
    }

    public function cancel()
    {
        $this->status = 'cancelled';
        $this->save();
    }

    public function addParticipant($userId, $role = 'participant')
    {
        return $this->participants()->create([
            'user_id' => $userId,
            'role' => $role
        ]);
    }

    public function getDurationFormattedAttribute()
    {
        if (!$this->duration_seconds) {
            return null;
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function getParticipantCountAttribute()
    {
        return $this->participants()->count();
    }
}
