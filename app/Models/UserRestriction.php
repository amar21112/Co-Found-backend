<?php

namespace App\Models;

use App\Enums\RestrictionType;
use Database\Factories\UserRestrictionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $restricted_by
 * @property RestrictionType $restriction_type
 * @property string|null $reason
 * @property int|null $duration_hours
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property bool $is_active
 * @property string|null $lifted_by
 * @property Carbon|null $lifted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|UserRestriction create(array $attributes = [])
 * @method static Builder|UserRestriction find($id, $columns = ['*'])
 * @method static Builder|UserRestriction findOrFail($id, $columns = ['*'])
 * @method static Builder|UserRestriction first($columns = ['*'])
 * @method static Builder|UserRestriction firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|UserRestriction firstOrFail($columns = ['*'])
 * @method static Builder|UserRestriction newModelQuery()
 * @method static Builder|UserRestriction newQuery()
 * @method static Builder|UserRestriction query()
 * @method static Builder|UserRestriction where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|UserRestriction whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|UserRestriction with($relations, $callback = null)
 *
 * @method static Builder|UserRestriction whereId($value)
 * @method static Builder|UserRestriction whereUserId($value)
 * @method static Builder|UserRestriction whereRestrictedBy($value)
 * @method static Builder|UserRestriction whereRestrictionType($value)
 * @method static Builder|UserRestriction whereReason($value)
 * @method static Builder|UserRestriction whereDurationHours($value)
 * @method static Builder|UserRestriction whereStartsAt($value)
 * @method static Builder|UserRestriction whereExpiresAt($value)
 * @method static Builder|UserRestriction whereIsActive($value)
 * @method static Builder|UserRestriction whereLiftedBy($value)
 * @method static Builder|UserRestriction whereLiftedAt($value)
 * @method static Builder|UserRestriction whereCreatedAt($value)
 * @method static Builder|UserRestriction whereUpdatedAt($value)
 *
 * @property-read User $user
 * @property-read User $restrictedBy
 * @property-read User|null $liftedBy
 *
 * @method static UserRestrictionFactory factory($count = null, $state = [])
 */
class UserRestriction extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'user_id', 'restricted_by', 'restriction_type', 'reason',
        'duration_hours', 'starts_at', 'expires_at',
        'is_active', 'lifted_by', 'lifted_at',
    ];

    protected $casts = [
        'restriction_type' => RestrictionType::class,
        'starts_at'        => 'datetime',
        'expires_at'       => 'datetime',
        'lifted_at'        => 'datetime',
        'is_active'        => 'boolean',
        'duration_hours'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restrictedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restricted_by');
    }

    public function liftedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }

    public function isPermanent(): bool
    {
        return $this->duration_hours === null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function blocksLogin(): bool
    {
        return $this->restriction_type->blocksLogin();
    }
}
