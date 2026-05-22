<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $session_token
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $device_info
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|Session create(array $attributes = [])
 * @method static Builder|Session find($id, $columns = ['*'])
 * @method static Builder|Session findOrFail($id, $columns = ['*'])
 * @method static Builder|Session first($columns = ['*'])
 * @method static Builder|Session firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|Session firstOrFail($columns = ['*'])
 * @method static Builder|Session newModelQuery()
 * @method static Builder|Session newQuery()
 * @method static Builder|Session query()
 * @method static Builder|Session where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|Session whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|Session with($relations, $callback = null)
 *
 * @method static Builder|Session whereId($value)
 * @method static Builder|Session whereUserId($value)
 * @method static Builder|Session whereSessionToken($value)
 * @method static Builder|Session whereIpAddress($value)
 * @method static Builder|Session whereUserAgent($value)
 * @method static Builder|Session whereDeviceInfo($value)
 * @method static Builder|Session whereExpiresAt($value)
 * @method static Builder|Session whereCreatedAt($value)
 * @method static Builder|Session whereUpdatedAt($value)
 *
 * @property-read User $user
 */
class Session extends Model
{
    use HasUuids;

    public $timestamps    = true; // matches migration
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';
    protected $table      = 'sessions';

    protected $fillable = [
        'user_id', 'session_token', 'ip_address',
        'user_agent', 'device_info', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
