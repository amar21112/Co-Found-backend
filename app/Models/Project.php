<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->hasMany(ProjectMilestone::class)->orderBy('order_index');
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

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
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
