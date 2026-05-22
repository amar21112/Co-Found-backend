<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string $sender_id
 * @property string $message_type
 * @property string $content
 * @property array|null $formatted_content
 * @property string|null $replied_to_message_id
 * @property bool $is_pinned
 * @property bool $is_edited
 * @property Carbon|null $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|Message create(array $attributes = [])
 * @method static Builder|Message find($id, $columns = ['*'])
 * @method static Builder|Message findOrFail($id, $columns = ['*'])
 * @method static Builder|Message first($columns = ['*'])
 * @method static Builder|Message firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|Message firstOrFail($columns = ['*'])
 * @method static Builder|Message newModelQuery()
 * @method static Builder|Message newQuery()
 * @method static Builder|Message query()
 * @method static Builder|Message where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|Message whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|Message with($relations, $callback = null)
 * @method static Builder|Message withTrashed()
 * @method static Builder|Message onlyTrashed()
 *
 * @method static Builder|Message whereId($value)
 * @method static Builder|Message whereConversationId($value)
 * @method static Builder|Message whereSenderId($value)
 * @method static Builder|Message whereMessageType($value)
 * @method static Builder|Message whereContent($value)
 * @method static Builder|Message whereFormattedContent($value)
 * @method static Builder|Message whereRepliedToMessageId($value)
 * @method static Builder|Message whereIsPinned($value)
 * @method static Builder|Message whereIsEdited($value)
 * @method static Builder|Message whereDeletedAt($value)
 * @method static Builder|Message whereCreatedAt($value)
 * @method static Builder|Message whereUpdatedAt($value)
 *
 * @property-read Conversation $conversation
 * @property-read User $sender
 * @property-read Message|null $repliedTo
 * @property-read Collection|Message[] $replies
 * @property-read Collection|MessageReadReceipt[] $readReceipts
 * @property-read Collection|MessageReaction[] $reactions
 * @property-read Collection|SharedFile[] $sharedFiles
 *
 * @method static MessageFactory factory($count = null, $state = [])
 */
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
