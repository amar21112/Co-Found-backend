<?php

namespace App\Models;

use Database\Factories\CollaborationRatingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $rater_id
 * @property string $rated_user_id
 * @property string|null $project_id
 * @property int|null $communication_rating
 * @property int|null $reliability_rating
 * @property int|null $skill_rating
 * @property int|null $problem_solving_rating
 * @property int|null $teamwork_rating
 * @property float|null $overall_rating
 * @property string|null $written_feedback
 * @property string $visibility
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|CollaborationRating create(array $attributes = [])
 * @method static Builder|CollaborationRating find($id, $columns = ['*'])
 * @method static Builder|CollaborationRating findOrFail($id, $columns = ['*'])
 * @method static Builder|CollaborationRating first($columns = ['*'])
 * @method static Builder|CollaborationRating firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|CollaborationRating firstOrFail($columns = ['*'])
 * @method static Builder|CollaborationRating newModelQuery()
 * @method static Builder|CollaborationRating newQuery()
 * @method static Builder|CollaborationRating query()
 * @method static Builder|CollaborationRating where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|CollaborationRating whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|CollaborationRating with($relations, $callback = null)
 *
 * @method static Builder|CollaborationRating whereId($value)
 * @method static Builder|CollaborationRating whereRaterId($value)
 * @method static Builder|CollaborationRating whereRatedUserId($value)
 * @method static Builder|CollaborationRating whereProjectId($value)
 * @method static Builder|CollaborationRating whereCommunicationRating($value)
 * @method static Builder|CollaborationRating whereReliabilityRating($value)
 * @method static Builder|CollaborationRating whereSkillRating($value)
 * @method static Builder|CollaborationRating whereProblemSolvingRating($value)
 * @method static Builder|CollaborationRating whereTeamworkRating($value)
 * @method static Builder|CollaborationRating whereOverallRating($value)
 * @method static Builder|CollaborationRating whereWrittenFeedback($value)
 * @method static Builder|CollaborationRating whereVisibility($value)
 * @method static Builder|CollaborationRating whereCreatedAt($value)
 * @method static Builder|CollaborationRating whereUpdatedAt($value)
 *
 * @property-read User $rater
 * @property-read User $ratedUser
 * @property-read Project $project
 *
 * @method static CollaborationRatingFactory factory($count = null, $state = [])
 */
class CollaborationRating extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'rater_id', 'rated_user_id', 'project_id',
        'communication_rating', 'reliability_rating', 'skill_rating',
        'problem_solving_rating', 'teamwork_rating', 'overall_rating',
        'written_feedback', 'visibility',
    ];

    protected $casts = [
        'communication_rating'   => 'integer',
        'reliability_rating'     => 'integer',
        'skill_rating'           => 'integer',
        'problem_solving_rating' => 'integer',
        'teamwork_rating'        => 'integer',
        'overall_rating'         => 'float',
    ];

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function ratedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
