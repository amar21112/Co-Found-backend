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
 * @property Carbon|null $read_at
 *
 * @method static Builder|MessageReadReceipt create(array $attributes = [])
 * @method static Builder|MessageReadReceipt find($id, $columns = ['*'])
 * @method static Builder|MessageReadReceipt findOrFail($id, $columns = ['*'])
 * @method static Builder|MessageReadReceipt first($columns = ['*'])
 * @method static Builder|MessageReadReceipt firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|MessageReadReceipt firstOrFail($columns = ['*'])
 * @method static Builder|MessageReadReceipt newModelQuery()
 * @method static Builder|MessageReadReceipt newQuery()
 * @method static Builder|MessageReadReceipt query()
 * @method static Builder|MessageReadReceipt where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|MessageReadReceipt whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|MessageReadReceipt with($relations, $callback = null)
 *
 * @method static Builder|MessageReadReceipt whereId($value)
 * @method static Builder|MessageReadReceipt whereMessageId($value)
 * @method static Builder|MessageReadReceipt whereUserId($value)
 * @method static Builder|MessageReadReceipt whereReadAt($value)
 *
 * @property-read Message $message
 * @property-read User $user
 */
class MessageReadReceipt extends Model
{
    use HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = ['message_id', 'user_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
