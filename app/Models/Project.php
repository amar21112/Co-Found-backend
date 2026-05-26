<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
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
 * @property string $owner_id
 * @property string $title
 * @property string $slug
 * @property string|null $short_description
 * @property string|null $full_description
 * @property string|null $category
 * @property string $status
 * @property string $visibility
 * @property int|null $team_size_min
 * @property int|null $team_size_max
 * @property int $current_team_size
 * @property Carbon|null $start_date
 * @property Carbon|null $target_completion_date
 * @property Carbon|null $actual_completion_date
 * @property bool $is_accepting_applications
 * @property Carbon|null $application_deadline
 * @property int $view_count
 * @property int $application_count
 * @property Carbon|null $published_at
 * @property Carbon|null $archived_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|Project create(array $attributes = [])
 * @method static Builder|Project find($id, $columns = ['*'])
 * @method static Builder|Project findOrFail($id, $columns = ['*'])
 * @method static Builder|Project first($columns = ['*'])
 * @method static Builder|Project firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|Project firstOrFail($columns = ['*'])
 * @method static Builder|Project newModelQuery()
 * @method static Builder|Project newQuery()
 * @method static Builder|Project query()
 * @method static Builder|Project where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|Project whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|Project with($relations, $callback = null)
 *
 * @method static Builder|Project whereId($value)
 * @method static Builder|Project whereOwnerId($value)
 * @method static Builder|Project whereTitle($value)
 * @method static Builder|Project whereSlug($value)
 * @method static Builder|Project whereShortDescription($value)
 * @method static Builder|Project whereFullDescription($value)
 * @method static Builder|Project whereCategory($value)
 * @method static Builder|Project whereStatus($value)
 * @method static Builder|Project whereVisibility($value)
 * @method static Builder|Project whereTeamSizeMin($value)
 * @method static Builder|Project whereTeamSizeMax($value)
 * @method static Builder|Project whereCurrentTeamSize($value)
 * @method static Builder|Project whereStartDate($value)
 * @method static Builder|Project whereTargetCompletionDate($value)
 * @method static Builder|Project whereActualCompletionDate($value)
 * @method static Builder|Project whereIsAcceptingApplications($value)
 * @method static Builder|Project whereApplicationDeadline($value)
 * @method static Builder|Project whereViewCount($value)
 * @method static Builder|Project whereApplicationCount($value)
 * @method static Builder|Project wherePublishedAt($value)
 * @method static Builder|Project whereArchivedAt($value)
 * @method static Builder|Project whereCreatedAt($value)
 * @method static Builder|Project whereUpdatedAt($value)
 *
 * @property-read User $owner
 * @property-read Collection|ProjectSkill[] $skills
 * @property-read Collection|ProjectRole[] $roles
 * @property-read Collection|ProjectMilestone[] $milestones
 * @property-read Collection|ProjectTeamMember[] $teamMembers
 * @property-read Collection|ProjectTeamMember[] $activeTeamMembers
 * @property-read Collection|ProjectApplication[] $applications
 * @property-read Collection|ProjectApplication[] $pendingApplications
 * @property-read Collection|CollaborationInvitation[] $invitations
 * @property-read Collection|MatchModel[] $matches
 * @property-read Collection|CollaborationRating[] $ratings
 * @property-read Collection|VideoCall[] $videoCalls
 *
 * @method static ProjectFactory factory($count = null, $state = [])
 */
class Project extends Model
{
    use HasFactory, HasUuids;

    // NOTE: No SoftDeletes — deleted_at column does not exist in the schema.
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'owner_id', 'title', 'slug', 'short_description', 'full_description',
        'category', 'status', 'visibility',
        'team_size_min', 'team_size_max', 'current_team_size',
        'start_date', 'target_completion_date', 'actual_completion_date',
        'is_accepting_applications', 'application_deadline',
        'view_count', 'application_count',
        'published_at', 'archived_at',
    ];

    protected $casts = [
        'start_date'               => 'date',
        'target_completion_date'   => 'date',
        'actual_completion_date'   => 'date',
        'application_deadline'     => 'date',
        'is_accepting_applications'=> 'boolean',
        'team_size_min'            => 'integer',
        'team_size_max'            => 'integer',
        'current_team_size'        => 'integer',
        'view_count'               => 'integer',
        'application_count'        => 'integer',
        'published_at'             => 'datetime',
        'archived_at'              => 'datetime',
    ];

    // =========================================================================
    // Relations
    // =========================================================================

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(ProjectSkill::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(ProjectRole::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(ProjectTeamMember::class);
    }

    public function activeTeamMembers(): HasMany
    {
        return $this->hasMany(ProjectTeamMember::class)->where('is_active', true);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class);
    }

    public function pendingApplications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class)->where('status', 'pending');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CollaborationInvitation::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchModel::class, 'matched_project_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(CollaborationRating::class);
    }

    public function videoCalls(): HasMany
    {
        return $this->hasMany(VideoCall::class);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    public function isActive(): bool      { return $this->status === 'active'; }
    public function isCompleted(): bool   { return $this->status === 'completed'; }
    public function isPublic(): bool      { return $this->visibility === 'public'; }
    public function isAcceptingApps(): bool { return (bool) $this->is_accepting_applications; }
}
