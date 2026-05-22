<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $message_id
 * @property string $user_id
 * @property string $reaction
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|MessageReaction create(array $attributes = [])
 * @method static Builder|MessageReaction find($id, $columns = ['*'])
 * @method static Builder|MessageReaction findOrFail($id, $columns = ['*'])
 * @method static Builder|MessageReaction first($columns = ['*'])
 * @method static Builder|MessageReaction firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|MessageReaction firstOrFail($columns = ['*'])
 * @method static Builder|MessageReaction newModelQuery()
 * @method static Builder|MessageReaction newQuery()
 * @method static Builder|MessageReaction query()
 * @method static Builder|MessageReaction where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|MessageReaction whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|MessageReaction with($relations, $callback = null)
 *
 * @method static Builder|MessageReaction whereId($value)
 * @method static Builder|MessageReaction whereMessageId($value)
 * @method static Builder|MessageReaction whereUserId($value)
 * @method static Builder|MessageReaction whereReaction($value)
 * @method static Builder|MessageReaction whereCreatedAt($value)
 * @method static Builder|MessageReaction whereUpdatedAt($value)
 *
 * @property-read Message $message
 * @property-read User $user
 */
class MessageReaction extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = ['message_id', 'user_id', 'reaction'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
