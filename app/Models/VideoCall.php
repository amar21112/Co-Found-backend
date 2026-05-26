<?php

namespace App\Models;

use App\Enums\CallStatus;
use App\Enums\CallType;
use Database\Factories\VideoCallFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property CallType $call_type
 * @property string|null $conversation_id
 * @property string|null $project_id
 * @property string $initiated_by
 * @property string $room_name
 * @property string|null $room_url
 * @property Carbon|null $start_time
 * @property Carbon|null $end_time
 * @property int|null $duration_seconds
 * @property CallStatus $status
 * @property string|null $recording_url
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|VideoCall create(array $attributes = [])
 * @method static Builder|VideoCall find($id, $columns = ['*'])
 * @method static Builder|VideoCall findOrFail($id, $columns = ['*'])
 * @method static Builder|VideoCall first($columns = ['*'])
 * @method static Builder|VideoCall firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|VideoCall firstOrFail($columns = ['*'])
 * @method static Builder|VideoCall latest($column = null)
 * @method static Builder|VideoCall newModelQuery()
 * @method static Builder|VideoCall newQuery()
 * @method static Builder|VideoCall query()
 * @method static Builder|VideoCall where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|VideoCall whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|VideoCall with($relations, $callback = null)
 *
 * @method static Builder|VideoCall whereId($value)
 * @method static Builder|VideoCall whereCallType($value)
 * @method static Builder|VideoCall whereConversationId($value)
 * @method static Builder|VideoCall whereProjectId($value)
 * @method static Builder|VideoCall whereInitiatedBy($value)
 * @method static Builder|VideoCall whereRoomName($value)
 * @method static Builder|VideoCall whereRoomUrl($value)
 * @method static Builder|VideoCall whereStartTime($value)
 * @method static Builder|VideoCall whereEndTime($value)
 * @method static Builder|VideoCall whereDurationSeconds($value)
 * @method static Builder|VideoCall whereStatus($value)
 * @method static Builder|VideoCall whereRecordingUrl($value)
 * @method static Builder|VideoCall whereCreatedAt($value)
 * @method static Builder|VideoCall whereUpdatedAt($value)
 *
 * @property-read User $initiator
 * @property-read Project|null $project
 * @property-read Collection|CallParticipant[] $participants
 * @property-read Collection|CallParticipant[] $activeParticipants
 *
 * @method static VideoCallFactory factory($count = null, $state = [])
 */
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
