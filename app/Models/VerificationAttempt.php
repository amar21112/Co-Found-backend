<?php

namespace App\Models;

use Database\Factories\VerificationAttemptFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property int $attempt_number
 * @property array|null $submission_data
 * @property string|null $result
 * @property string|null $failure_reason
 * @property string|null $ip_address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|VerificationAttempt create(array $attributes = [])
 * @method static Builder|VerificationAttempt find($id, $columns = ['*'])
 * @method static Builder|VerificationAttempt findOrFail($id, $columns = ['*'])
 * @method static Builder|VerificationAttempt first($columns = ['*'])
 * @method static Builder|VerificationAttempt firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|VerificationAttempt firstOrFail($columns = ['*'])
 * @method static Builder|VerificationAttempt newModelQuery()
 * @method static Builder|VerificationAttempt newQuery()
 * @method static Builder|VerificationAttempt query()
 * @method static Builder|VerificationAttempt where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|VerificationAttempt whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|VerificationAttempt with($relations, $callback = null)
 *
 * @method static Builder|VerificationAttempt whereId($value)
 * @method static Builder|VerificationAttempt whereUserId($value)
 * @method static Builder|VerificationAttempt whereAttemptNumber($value)
 * @method static Builder|VerificationAttempt whereSubmissionData($value)
 * @method static Builder|VerificationAttempt whereResult($value)
 * @method static Builder|VerificationAttempt whereFailureReason($value)
 * @method static Builder|VerificationAttempt whereIpAddress($value)
 * @method static Builder|VerificationAttempt whereCreatedAt($value)
 * @method static Builder|VerificationAttempt whereUpdatedAt($value)
 *
 * @property-read User $user
 *
 * @method static VerificationAttemptFactory factory($count = null, $state = [])
 */
class VerificationAttempt extends Model
{
    use HasUuids, HasFactory;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'user_id', 'attempt_number', 'submission_data',
        'result', 'failure_reason', 'ip_address',
    ];

    protected $casts = [
        'submission_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
