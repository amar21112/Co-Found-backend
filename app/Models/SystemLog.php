<?php

namespace App\Models;

use Database\Factories\SystemLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $log_level
 * @property string $component
 * @property string $event_type
 * @property string $message
 * @property array|null $details
 * @property string|null $ip_address
 * @property string|null $user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|SystemLog create(array $attributes = [])
 * @method static Builder|SystemLog find($id, $columns = ['*'])
 * @method static Builder|SystemLog findOrFail($id, $columns = ['*'])
 * @method static Builder|SystemLog first($columns = ['*'])
 * @method static Builder|SystemLog firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|SystemLog firstOrFail($columns = ['*'])
 * @method static Builder|SystemLog newModelQuery()
 * @method static Builder|SystemLog newQuery()
 * @method static Builder|SystemLog query()
 * @method static Builder|SystemLog where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|SystemLog whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|SystemLog with($relations, $callback = null)
 *
 * @method static Builder|SystemLog whereId($value)
 * @method static Builder|SystemLog whereLogLevel($value)
 * @method static Builder|SystemLog whereComponent($value)
 * @method static Builder|SystemLog whereEventType($value)
 * @method static Builder|SystemLog whereMessage($value)
 * @method static Builder|SystemLog whereDetails($value)
 * @method static Builder|SystemLog whereIpAddress($value)
 * @method static Builder|SystemLog whereUserId($value)
 * @method static Builder|SystemLog whereCreatedAt($value)
 * @method static Builder|SystemLog whereUpdatedAt($value)
 *
 * @property-read User|null $user
 *
 * @method static SystemLogFactory factory($count = null, $state = [])
 */
class SystemLog extends Model
{
    use HasUuids, HasFactory;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'log_level', 'component', 'event_type',
        'message', 'details', 'ip_address', 'user_id',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
