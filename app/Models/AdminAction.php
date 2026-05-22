<?php

namespace App\Models;

use Database\Factories\AdminActionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $admin_id
 * @property string $action_type
 * @property string|null $target_type
 * @property string|null $target_id
 * @property array|null $details
 * @property string|null $ip_address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|AdminAction create(array $attributes = [])
 * @method static Builder|AdminAction find($id, $columns = ['*'])
 * @method static Builder|AdminAction findOrFail($id, $columns = ['*'])
 * @method static Builder|AdminAction first($columns = ['*'])
 * @method static Builder|AdminAction firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|AdminAction firstOrFail($columns = ['*'])
 * @method static Builder|AdminAction newModelQuery()
 * @method static Builder|AdminAction newQuery()
 * @method static Builder|AdminAction query()
 * @method static Builder|AdminAction where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|AdminAction whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|AdminAction with($relations, $callback = null)
 *
 * @method static Builder|AdminAction whereId($value)
 * @method static Builder|AdminAction whereAdminId($value)
 * @method static Builder|AdminAction whereActionType($value)
 * @method static Builder|AdminAction whereTargetType($value)
 * @method static Builder|AdminAction whereTargetId($value)
 * @method static Builder|AdminAction whereDetails($value)
 * @method static Builder|AdminAction whereIpAddress($value)
 * @method static Builder|AdminAction whereCreatedAt($value)
 * @method static Builder|AdminAction whereUpdatedAt($value)
 *
 * @property-read User $admin
 *
 * @method static AdminActionFactory factory($count = null, $state = [])
 */
class AdminAction extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'admin_id', 'action_type', 'target_type',
        'target_id', 'details', 'ip_address',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
