<?php

namespace App\Models;

use Database\Factories\UserConnectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $requester_id
 * @property string $recipient_id
 * @property string $status
 * @property string|null $connection_type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|UserConnection create(array $attributes = [])
 * @method static Builder|UserConnection find($id, $columns = ['*'])
 * @method static Builder|UserConnection findOrFail($id, $columns = ['*'])
 * @method static Builder|UserConnection first($columns = ['*'])
 * @method static Builder|UserConnection firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|UserConnection firstOrFail($columns = ['*'])
 * @method static Builder|UserConnection newModelQuery()
 * @method static Builder|UserConnection newQuery()
 * @method static Builder|UserConnection query()
 * @method static Builder|UserConnection where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|UserConnection whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|UserConnection with($relations, $callback = null)
 *
 * @method static Builder|UserConnection whereId($value)
 * @method static Builder|UserConnection whereRequesterId($value)
 * @method static Builder|UserConnection whereRecipientId($value)
 * @method static Builder|UserConnection whereStatus($value)
 * @method static Builder|UserConnection whereConnectionType($value)
 * @method static Builder|UserConnection whereCreatedAt($value)
 * @method static Builder|UserConnection whereUpdatedAt($value)
 *
 * @property-read User $requester
 * @property-read User $recipient
 *
 * @method static UserConnectionFactory factory($count = null, $state = [])
 */
class UserConnection extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'requester_id', 'recipient_id', 'status', 'connection_type',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function isAccepted(): bool { return $this->status === 'accepted'; }
    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isBlocked(): bool  { return $this->status === 'blocked'; }
}
