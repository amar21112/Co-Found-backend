<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'projects';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'owner_id',
        'title',
        'slug',
        'short_description',
        'full_description',
        'category',
        'status',
        'visibility',
        'team_size_min',
        'team_size_max',
        'current_team_size',
        'start_date',
        'target_completion_date',
        'actual_completion_date',
        'is_accepting_applications',
        'application_deadline',
        'view_count',
        'application_count',
        'published_at',
        'archived_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'application_deadline' => 'date',
        'is_accepting_applications' => 'boolean',
        'view_count' => 'integer',
        'application_count' => 'integer',
        'published_at' => 'datetime',
        'archived_at' => 'datetime'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function skills()
    {
        return $this->hasMany(ProjectSkill::class, 'project_id');
    }

    public function roles()
    {
        return $this->hasMany(ProjectRole::class, 'project_id');
    }

    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class, 'project_id')->orderBy('order_index');
    }

    public function teamMembers()
    {
        return $this->hasMany(ProjectTeamMember::class, 'project_id');
    }

    public function applications()
    {
        return $this->hasMany(ProjectApplication::class, 'project_id');
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'project_id');
    }

    public function videoCalls()
    {
        return $this->hasMany(VideoCall::class, 'project_id');
    }

    public function collaborationInvitations()
    {
        return $this->hasMany(CollaborationInvitation::class, 'project_id');
    }

    public function collaborationRatings()
    {
        return $this->hasMany(CollaborationRating::class, 'project_id');
    }

    public function matches()
    {
        return $this->hasMany(MatchModel::class, 'matched_project_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePlanning($query)
    {
        return $query->where('status', 'planning');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeAcceptingApplications($query)
    {
        return $query->where('is_accepting_applications', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopePopular($query)
    {
        return $query->orderBy('view_count', 'desc');
    }

    public function getTeamMembersCountAttribute()
    {
        return $this->teamMembers()->where('is_active', true)->count();
    }

    public function getOpenPositionsCountAttribute()
    {
        return $this->roles()->sum('positions_needed') - $this->roles()->sum('positions_filled');
    }

    public function getProgressPercentageAttribute()
    {
        $completed = $this->milestones()->where('status', 'completed')->count();
        $total = $this->milestones()->count();

        if ($total === 0) {
            return 0;
        }

        return round(($completed / $total) * 100);
    }

    public function isOwner($userId)
    {
        return $this->owner_id === $userId;
    }

    public function isMember($userId)
    {
        return $this->teamMembers()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->exists();
    }

    public function hasApplied($userId)
    {
        return $this->applications()
            ->where('applicant_id', $userId)
            ->exists();
    }
}
