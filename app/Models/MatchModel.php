<?php

namespace App\Models;

use App\Enums\MatchType;
use Database\Factories\MatchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $matched_user_id
 * @property string|null $matched_project_id
 * @property MatchType $match_type
 * @property float $compatibility_score
 * @property array|null $match_reasons
 * @property bool $viewed
 * @property Carbon|null $viewed_at
 * @property bool $saved
 * @property bool $action_taken
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|MatchModel create(array $attributes = [])
 * @method static Builder|MatchModel find($id, $columns = ['*'])
 * @method static Builder|MatchModel findOrFail($id, $columns = ['*'])
 * @method static Builder|MatchModel first($columns = ['*'])
 * @method static Builder|MatchModel firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|MatchModel firstOrFail($columns = ['*'])
 * @method static Builder|MatchModel newModelQuery()
 * @method static Builder|MatchModel newQuery()
 * @method static Builder|MatchModel query()
 * @method static Builder|MatchModel where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|MatchModel whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|MatchModel with($relations, $callback = null)
 *
 * @method static Builder|MatchModel whereId($value)
 * @method static Builder|MatchModel whereUserId($value)
 * @method static Builder|MatchModel whereMatchedUserId($value)
 * @method static Builder|MatchModel whereMatchedProjectId($value)
 * @method static Builder|MatchModel whereMatchType($value)
 * @method static Builder|MatchModel whereCompatibilityScore($value)
 * @method static Builder|MatchModel whereMatchReasons($value)
 * @method static Builder|MatchModel whereViewed($value)
 * @method static Builder|MatchModel whereViewedAt($value)
 * @method static Builder|MatchModel whereSaved($value)
 * @method static Builder|MatchModel whereActionTaken($value)
 * @method static Builder|MatchModel whereExpiresAt($value)
 * @method static Builder|MatchModel whereCreatedAt($value)
 * @method static Builder|MatchModel whereUpdatedAt($value)
 *
 * @property-read User $user
 * @property-read User $matchedUser
 * @property-read Project $matchedProject
 * @property-read Collection|MatchFeedback[] $feedback
 *
 * @method static MatchFactory factory($count = null, $state = [])
 */
class MatchModel extends Model
{
    use HasFactory, HasUuids;

    protected $table      = 'matches';
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'user_id', 'matched_user_id', 'matched_project_id',
        'match_type', 'compatibility_score', 'match_reasons',
        'viewed', 'viewed_at', 'saved', 'action_taken', 'expires_at',
    ];

    protected $casts = [
        'match_type'          => MatchType::class,
        'compatibility_score' => 'float',
        'match_reasons'       => 'array',
        'viewed'              => 'boolean',
        'saved'               => 'boolean',
        'action_taken'        => 'boolean',
        'viewed_at'           => 'datetime',
        'expires_at'          => 'datetime',
    ];

    /**
     * Get the factory instance for the model.
     */
    protected static function newFactory(): MatchFactory
    {
        return MatchFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Populated when match_type = 'user_to_user' */
    public function matchedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_user_id');
    }

    /** Populated when match_type = 'user_to_project' */
    public function matchedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'matched_project_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(MatchFeedback::class, 'match_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function isUserMatch(): bool
    {
        return $this->match_type === MatchType::Collaborator;
    }

    public function isProjectMatch(): bool
    {
        return $this->match_type === MatchType::Project;
    }
}
