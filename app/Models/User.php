<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'email',
        'username',
        'password_hash',
        'full_name',
        'profile_picture_url',
        'bio',
        'location',
        'website_url',
        'linkedin_url',
        'github_url',
        'role',
        'account_status',
        'email_verified',
        'identity_verified',
        'identity_verification_level',
        'email_verification_token',
        'email_verification_expires',
        'last_login_at',
        'last_login_ip',
        'login_attempts',
        'locked_until'
    ];

    protected $hidden = [
        'password_hash',
        'email_verification_token',
        'remember_token'
    ];

    protected $casts = [
        'email_verified' => 'boolean',
        'identity_verified' => 'boolean',
        'email_verification_expires' => 'datetime',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // Relationships
    public function skills()
    {
        return $this->hasMany(UserSkill::class, 'user_id');
    }

    public function skillEndorsements()
    {
        return $this->hasManyThrough(SkillEndorsement::class, UserSkill::class, 'user_id', 'endorsed_by_user_id');
    }

    public function portfolioItems()
    {
        return $this->hasMany(PortfolioItem::class, 'user_id');
    }

    public function sessions()
    {
        return $this->hasMany(Session::class, 'user_id');
    }

    public function passwordResets()
    {
        return $this->hasMany(PasswordReset::class, 'user_id');
    }

    public function identityVerification()
    {
        return $this->hasOne(IdentityVerification::class, 'user_id');
    }

    public function verificationAttempts()
    {
        return $this->hasMany(VerificationAttempt::class, 'user_id');
    }

    public function ownedProjects()
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function teamMemberships()
    {
        return $this->hasMany(ProjectTeamMember::class, 'user_id');
    }

    public function projectApplications()
    {
        return $this->hasMany(ProjectApplication::class, 'applicant_id');
    }

    public function reviewedApplications()
    {
        return $this->hasMany(ProjectApplication::class, 'reviewed_by');
    }

    public function sentConnections()
    {
        return $this->hasMany(UserConnection::class, 'requester_id');
    }

    public function receivedConnections()
    {
        return $this->hasMany(UserConnection::class, 'recipient_id');
    }

    public function sentInvitations()
    {
        return $this->hasMany(CollaborationInvitation::class, 'sender_id');
    }

    public function receivedInvitations()
    {
        return $this->hasMany(CollaborationInvitation::class, 'recipient_id');
    }

    public function matches()
    {
        return $this->hasMany(MatchModel::class, 'user_id');
    }

    public function matchedUsers()
    {
        return $this->hasMany(MatchModel::class, 'matched_user_id');
    }

    public function givenRatings()
    {
        return $this->hasMany(CollaborationRating::class, 'rater_id');
    }

    public function receivedRatings()
    {
        return $this->hasMany(CollaborationRating::class, 'rated_user_id');
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants', 'user_id', 'conversation_id')
            ->withPivot('joined_at', 'left_at', 'is_admin', 'muted', 'muted_until')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function messageReadReceipts()
    {
        return $this->hasMany(MessageReadReceipt::class, 'user_id');
    }

    public function messageReactions()
    {
        return $this->hasMany(MessageReaction::class, 'user_id');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'uploader_id');
    }

    public function sharedFiles()
    {
        return $this->hasMany(SharedFile::class, 'shared_by');
    }

    public function videoCallsInitiated()
    {
        return $this->hasMany(VideoCall::class, 'initiated_by');
    }

    public function callParticipations()
    {
        return $this->hasMany(CallParticipant::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function notificationPreference()
    {
        return $this->hasOne(NotificationPreference::class, 'user_id');
    }

    public function adminActions()
    {
        return $this->hasMany(AdminAction::class, 'admin_id');
    }

    public function reportsFiled()
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function reportsReceived()
    {
        return $this->hasMany(Report::class, 'reported_user_id');
    }

    public function reportsAssigned()
    {
        return $this->hasMany(Report::class, 'assigned_to');
    }

    public function reportsResolved()
    {
        return $this->hasMany(Report::class, 'resolved_by');
    }

    public function contentModerations()
    {
        return $this->hasMany(ContentModeration::class, 'moderator_id');
    }

    public function restrictionsGiven()
    {
        return $this->hasMany(UserRestriction::class, 'restricted_by');
    }

    public function restrictionsReceived()
    {
        return $this->hasMany(UserRestriction::class, 'user_id');
    }

    public function restrictionsLifted()
    {
        return $this->hasMany(UserRestriction::class, 'lifted_by');
    }

    public function systemLogs()
    {
        return $this->hasMany(SystemLog::class, 'user_id');
    }

    public function analyticsEvents()
    {
        return $this->hasMany(AnalyticsEvent::class, 'user_id');
    }

    public function settingsUpdated()
    {
        return $this->hasMany(SystemSetting::class, 'updated_by');
    }

    public function configurationChanges()
    {
        return $this->hasMany(ConfigurationHistory::class, 'changed_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('account_status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('identity_verified', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeOnline($query)
    {
        return $query->whereHas('sessions', function ($q) {
            $q->where('expires_at', '>', now());
        });
    }

    // Accessors
    public function getIsOnlineAttribute()
    {
        return $this->sessions()->where('expires_at', '>', now())->exists();
    }

    public function getProfileCompletenessAttribute()
    {
        $score = 0;
        if ($this->bio) $score += 20;
        if ($this->profile_picture_url) $score += 20;
        if ($this->skills()->count() >= 3) $score += 20;
        if ($this->portfolioItems()->count() >= 1) $score += 20;
        if ($this->linkedin_url || $this->github_url) $score += 20;
        return $score;
    }

    public function getAverageRatingAttribute()
    {
        return $this->receivedRatings()->avg('overall_rating') ?? 0;
    }
}
