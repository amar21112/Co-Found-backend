<?php

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $conversation_type
 * @property string|null $title
 * @property string|null $project_id
 * @property string $created_by
 * @property Carbon|null $last_message_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|Conversation create(array $attributes = [])
 * @method static Builder|Conversation find($id, $columns = ['*'])
 * @method static Builder|Conversation findOrFail($id, $columns = ['*'])
 * @method static Builder|Conversation first($columns = ['*'])
 * @method static Builder|Conversation firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|Conversation firstOrFail($columns = ['*'])
 * @method static Builder|Conversation newModelQuery()
 * @method static Builder|Conversation newQuery()
 * @method static Builder|Conversation query()
 * @method static Builder|Conversation where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|Conversation whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|Conversation with($relations, $callback = null)
 *
 * @method static Builder|Conversation whereId($value)
 * @method static Builder|Conversation whereConversationType($value)
 * @method static Builder|Conversation whereTitle($value)
 * @method static Builder|Conversation whereProjectId($value)
 * @method static Builder|Conversation whereCreatedBy($value)
 * @method static Builder|Conversation whereLastMessageAt($value)
 * @method static Builder|Conversation whereCreatedAt($value)
 * @method static Builder|Conversation whereUpdatedAt($value)
 *
 * @property-read User $creator
 * @property-read Project $project
 * @property-read Collection|ConversationParticipant[] $participants
 * @property-read Collection|ConversationParticipant[] $activeParticipants
 * @property-read Collection|Message[] $messages
 * @property-read Message|null $latestMessage
 * @property-read Collection|SharedFile[] $sharedFiles
 * @property-read Collection|VideoCall[] $videoCalls
 *
 * @method static ConversationFactory factory($count = null, $state = [])
 */
class Conversation extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'conversation_type', 'title', 'project_id',
        'created_by', 'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function activeParticipants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class)->whereNull('left_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany('created_at');
    }

    public function sharedFiles(): HasMany
    {
        return $this->hasMany(SharedFile::class);
    }

    public function videoCalls(): HasMany
    {
        return $this->hasMany(VideoCall::class);
    }

    public function isDirect(): bool  { return $this->conversation_type === 'direct'; }
    public function isGroup(): bool   { return $this->conversation_type === 'group'; }
    public function isProject(): bool { return $this->conversation_type === 'project'; }
}
