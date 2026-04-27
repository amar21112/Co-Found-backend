<?php

namespace App\Models;

use App\Enums\CallStatus;
use App\Enums\CallType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VideoCall extends Model
{
    use HasFactory, HasUuids;

    // Migration has timestamps() — keep $timestamps = true (default)
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'call_type', 'conversation_id', 'project_id', 'initiated_by',
        'room_name', 'room_url', 'start_time', 'end_time',
        'duration_seconds', 'status', 'recording_url',
    ];

    protected $casts = [
        'call_type'        => CallType::class,
        'status'           => CallStatus::class,
        'start_time'       => 'datetime',
        'end_time'         => 'datetime',
        'duration_seconds' => 'integer',
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

    public function activeParticipants(): HasMany
    {
        return $this->hasMany(CallParticipant::class, 'call_id')
            ->whereNull('left_at');
    }

    public function isActive(): bool
    {
        return $this->status === CallStatus::Active;
    }

    public function isEnded(): bool
    {
        return $this->status === CallStatus::Ended;
    }

    public function isScheduled(): bool
    {
        return $this->status === CallStatus::Scheduled;
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function hasParticipant(string $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }
}
