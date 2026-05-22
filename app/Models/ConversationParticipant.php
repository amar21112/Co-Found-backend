<?php

namespace App\Models;

use Database\Factories\ConversationParticipantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string $user_id
 * @property Carbon|null $joined_at
 * @property Carbon|null $left_at
 * @property bool $is_admin
 * @property bool $muted
 * @property Carbon|null $muted_until
 *
 * @method static Builder|ConversationParticipant create(array $attributes = [])
 * @method static Builder|ConversationParticipant find($id, $columns = ['*'])
 * @method static Builder|ConversationParticipant findOrFail($id, $columns = ['*'])
 * @method static Builder|ConversationParticipant first($columns = ['*'])
 * @method static Builder|ConversationParticipant firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|ConversationParticipant firstOrFail($columns = ['*'])
 * @method static Builder|ConversationParticipant newModelQuery()
 * @method static Builder|ConversationParticipant newQuery()
 * @method static Builder|ConversationParticipant query()
 * @method static Builder|ConversationParticipant where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|ConversationParticipant whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|ConversationParticipant with($relations, $callback = null)
 *
 * @method static Builder|ConversationParticipant whereId($value)
 * @method static Builder|ConversationParticipant whereConversationId($value)
 * @method static Builder|ConversationParticipant whereUserId($value)
 * @method static Builder|ConversationParticipant whereJoinedAt($value)
 * @method static Builder|ConversationParticipant whereLeftAt($value)
 * @method static Builder|ConversationParticipant whereIsAdmin($value)
 * @method static Builder|ConversationParticipant whereMuted($value)
 * @method static Builder|ConversationParticipant whereMutedUntil($value)
 *
 * @property-read Conversation $conversation
 * @property-read User $user
 *
 * @method static ConversationParticipantFactory factory($count = null, $state = [])
 */
class ConversationParticipant extends Model
{
    use HasUuids, HasFactory;

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
