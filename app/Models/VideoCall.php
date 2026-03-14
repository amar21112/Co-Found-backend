<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VideoCall extends Model
{
    use HasFactory, HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'call_type', 'conversation_id', 'project_id', 'initiated_by',
        'room_name', 'room_url', 'start_time', 'end_time',
        'duration_seconds', 'status', 'recording_url',
    ];

    protected $casts = [
        'start_time'       => 'datetime',
        'end_time'         => 'datetime',
        'duration_seconds' => 'integer',
        'created_at'       => 'datetime',
    ];

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CallParticipant::class, 'call_id');
    }

    public function isActive(): bool    { return $this->status === 'active'; }
    public function isEnded(): bool     { return $this->status === 'ended'; }
    public function isScheduled(): bool { return $this->status === 'scheduled'; }
}
