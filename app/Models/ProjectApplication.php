<?php

namespace App\Models;

use Database\Factories\ProjectApplicationFactory;
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
 * @property string $project_id
 * @property string $applicant_id
 * @property string|null $role_id
 * @property string|null $cover_message
 * @property string|null $proposed_role
 * @property string|null $availability
 * @property string $status
 * @property float|null $match_score
 * @property string|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $applied_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|ProjectApplication create(array $attributes = [])
 * @method static Builder|ProjectApplication find($id, $columns = ['*'])
 * @method static Builder|ProjectApplication findOrFail($id, $columns = ['*'])
 * @method static Builder|ProjectApplication first($columns = ['*'])
 * @method static Builder|ProjectApplication firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|ProjectApplication firstOrFail($columns = ['*'])
 * @method static Builder|ProjectApplication newModelQuery()
 * @method static Builder|ProjectApplication newQuery()
 * @method static Builder|ProjectApplication query()
 * @method static Builder|ProjectApplication where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|ProjectApplication whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|ProjectApplication with($relations, $callback = null)
 *
 * @method static Builder|ProjectApplication whereId($value)
 * @method static Builder|ProjectApplication whereProjectId($value)
 * @method static Builder|ProjectApplication whereApplicantId($value)
 * @method static Builder|ProjectApplication whereRoleId($value)
 * @method static Builder|ProjectApplication whereCoverMessage($value)
 * @method static Builder|ProjectApplication whereProposedRole($value)
 * @method static Builder|ProjectApplication whereAvailability($value)
 * @method static Builder|ProjectApplication whereStatus($value)
 * @method static Builder|ProjectApplication whereMatchScore($value)
 * @method static Builder|ProjectApplication whereReviewedBy($value)
 * @method static Builder|ProjectApplication whereReviewedAt($value)
 * @method static Builder|ProjectApplication whereAppliedAt($value)
 * @method static Builder|ProjectApplication whereCreatedAt($value)
 * @method static Builder|ProjectApplication whereUpdatedAt($value)
 *
 * @property-read Project $project
 * @property-read User $applicant
 * @property-read ProjectRole|null $role
 * @property-read User|null $reviewer
 * @property-read Collection|ApplicationSkill[] $applicationSkills
 *
 * @method static ProjectApplicationFactory factory($count = null, $state = [])
 */
class ProjectApplication extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'project_id', 'applicant_id', 'role_id',
        'cover_message', 'proposed_role', 'availability',
        'status', 'match_score', 'reviewed_by', 'reviewed_at', 'applied_at',
    ];

    protected $casts = [
        'match_score' => 'float',
        'reviewed_at' => 'datetime',
        'applied_at'  => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    /**
     * The formal project role slot this application targets.
     * Null when the applicant used proposed_role (free-text) instead.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(ProjectRole::class, 'role_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function applicationSkills(): HasMany
    {
        return $this->hasMany(ApplicationSkill::class, 'application_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** True when applying to a defined role slot; false when proposing own title */
    public function hasDefinedRole(): bool  { return $this->role_id !== null; }
    public function isPending(): bool       { return $this->status === 'pending'; }
    public function isAccepted(): bool      { return $this->status === 'accepted'; }
    public function isRejected(): bool      { return $this->status === 'rejected'; }
}
