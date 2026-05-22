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
 * @property string $reset_token
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|PasswordReset create(array $attributes = [])
 * @method static Builder|PasswordReset find($id, $columns = ['*'])
 * @method static Builder|PasswordReset findOrFail($id, $columns = ['*'])
 * @method static Builder|PasswordReset first($columns = ['*'])
 * @method static Builder|PasswordReset firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|PasswordReset firstOrFail($columns = ['*'])
 * @method static Builder|PasswordReset newModelQuery()
 * @method static Builder|PasswordReset newQuery()
 * @method static Builder|PasswordReset query()
 * @method static Builder|PasswordReset where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|PasswordReset whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|PasswordReset with($relations, $callback = null)
 *
 * @method static Builder|PasswordReset whereId($value)
 * @method static Builder|PasswordReset whereUserId($value)
 * @method static Builder|PasswordReset whereResetToken($value)
 * @method static Builder|PasswordReset whereExpiresAt($value)
 * @method static Builder|PasswordReset whereUsedAt($value)
 * @method static Builder|PasswordReset whereCreatedAt($value)
 *
 * @property-read User $user
 */
class PasswordReset extends Model
{
    use HasUuids;

    public $timestamps    = true; //matches migration
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';
    protected $table      = 'password_resets';

    protected $fillable = ['user_id', 'reset_token', 'expires_at', 'used_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool { return $this->expires_at->isPast(); }
    public function isUsed(): bool    { return $this->used_at !== null; }
}
