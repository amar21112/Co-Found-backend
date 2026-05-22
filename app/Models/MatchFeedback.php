<?php

namespace App\Models;

use App\Enums\FeedbackType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $match_id
 * @property string $user_id
 * @property FeedbackType $feedback_type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|MatchFeedback create(array $attributes = [])
 * @method static Builder|MatchFeedback find($id, $columns = ['*'])
 * @method static Builder|MatchFeedback findOrFail($id, $columns = ['*'])
 * @method static Builder|MatchFeedback first($columns = ['*'])
 * @method static Builder|MatchFeedback firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|MatchFeedback firstOrFail($columns = ['*'])
 * @method static Builder|MatchFeedback newModelQuery()
 * @method static Builder|MatchFeedback newQuery()
 * @method static Builder|MatchFeedback query()
 * @method static Builder|MatchFeedback where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|MatchFeedback whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|MatchFeedback with($relations, $callback = null)
 *
 * @method static Builder|MatchFeedback whereId($value)
 * @method static Builder|MatchFeedback whereMatchId($value)
 * @method static Builder|MatchFeedback whereUserId($value)
 * @method static Builder|MatchFeedback whereFeedbackType($value)
 * @method static Builder|MatchFeedback whereCreatedAt($value)
 * @method static Builder|MatchFeedback whereUpdatedAt($value)
 *
 * @property-read MatchModel $match
 * @property-read User $user
 */
class MatchFeedback extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = ['match_id', 'user_id', 'feedback_type'];

    protected $casts = [
        'feedback_type' => FeedbackType::class,
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
