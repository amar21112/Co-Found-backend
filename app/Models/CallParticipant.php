<?php

namespace App\Models;

use App\Enums\CallParticipantRole;
use Database\Factories\CallParticipantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $call_id
 * @property string $user_id
 * @property Carbon|null $joined_at
 * @property Carbon|null $left_at
 * @property int|null $duration_seconds
 * @property CallParticipantRole $role
 * @property string $active_token_jti
 *
 * @method static Builder|CallParticipant create(array $attributes = [])
 * @method static Builder|CallParticipant find($id, $columns = ['*'])
 * @method static Builder|CallParticipant findOrFail($id, $columns = ['*'])
 * @method static Builder|CallParticipant first($columns = ['*'])
 * @method static Builder|CallParticipant firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|CallParticipant firstOrFail($columns = ['*'])
 * @method static Builder|CallParticipant newModelQuery()
 * @method static Builder|CallParticipant newQuery()
 * @method static Builder|CallParticipant query()
 * @method static Builder|CallParticipant where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|CallParticipant whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|CallParticipant with($relations, $callback = null)
 *
 * @method static Builder|CallParticipant whereId($value)
 * @method static Builder|CallParticipant whereCallId($value)
 * @method static Builder|CallParticipant whereUserId($value)
 * @method static Builder|CallParticipant whereJoinedAt($value)
 * @method static Builder|CallParticipant whereLeftAt($value)
 * @method static Builder|CallParticipant whereDurationSeconds($value)
 * @method static Builder|CallParticipant whereRole($value)
 * @method static Builder|CallParticipant whereActiveTokenJti($value)
 *
 * @property-read VideoCall $call
 * @property-read User $user
 *
 * @method static CallParticipantFactory factory($count = null, $state = [])
 */
class CallParticipant extends Model
{
    use HasUuids, HasFactory;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'call_id',
        'user_id',
        'joined_at',
        'left_at',
        'duration_seconds',
        'role',
        'active_token_jti', // UUID of the JWT most recently issued to this participant
    ];

    protected $casts = [
        'role'             => CallParticipantRole::class,
        'joined_at'        => 'datetime',
        'left_at'          => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(VideoCall::class, 'call_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isHost(): bool
    {
        return $this->role === CallParticipantRole::Host;
    }

    public function isActiveInCall(): bool
    {
        return $this->left_at === null;
    }
}
