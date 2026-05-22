<?php

namespace App\Models;

use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $type
 * @property string $title
 * @property string $body
 * @property array|null $data
 * @property string $priority
 * @property bool $read
 * @property Carbon|null $read_at
 * @property Carbon|null $delivered_at
 * @property Carbon $created_at
 *
 * @method static Builder|Notification create(array $attributes = [])
 * @method static Builder|Notification find($id, $columns = ['*'])
 * @method static Builder|Notification findOrFail($id, $columns = ['*'])
 * @method static Builder|Notification first($columns = ['*'])
 * @method static Builder|Notification firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|Notification firstOrFail($columns = ['*'])
 * @method static Builder|Notification newModelQuery()
 * @method static Builder|Notification newQuery()
 * @method static Builder|Notification query()
 * @method static Builder|Notification where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|Notification whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|Notification with($relations, $callback = null)
 *
 * @method static Builder|Notification whereId($value)
 * @method static Builder|Notification whereUserId($value)
 * @method static Builder|Notification whereType($value)
 * @method static Builder|Notification whereTitle($value)
 * @method static Builder|Notification whereBody($value)
 * @method static Builder|Notification whereData($value)
 * @method static Builder|Notification wherePriority($value)
 * @method static Builder|Notification whereRead($value)
 * @method static Builder|Notification whereReadAt($value)
 * @method static Builder|Notification whereDeliveredAt($value)
 * @method static Builder|Notification whereCreatedAt($value)
 *
 * @property-read User $user
 *
 * @method static NotificationFactory factory($count = null, $state = [])
 */
class Notification extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'type', 'title', 'body', 'data',
        'priority', 'read', 'read_at', 'delivered_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read' => 'boolean',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        $this->update(['read' => true, 'read_at' => now()]);
    }

    public function isUnread(): bool
    {
        return !$this->read;
    }
    public function isHigh(): bool
    {
        return $this->priority === 'high';
    }
}
