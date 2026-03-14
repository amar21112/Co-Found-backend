<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'email', 'username', 'password', 'full_name',
        'profile_picture_url', 'bio', 'location', 'website_url',
        'linkedin_url', 'github_url', 'role', 'account_status',
        'email_verified', 'identity_verified', 'identity_verification_level',
        'email_verification_token', 'email_verification_expires',
        'last_login_at', 'last_login_ip', 'login_attempts', 'locked_until',
    ];

    protected $hidden = [
        'password_hash', 'email_verification_token',
        'remember_token',
    ];

    protected $casts = [
        'email_verified'            => 'boolean',
        'identity_verified'         => 'boolean',
        'email_verification_expires'=> 'datetime',
        'last_login_at'             => 'datetime',
        'locked_until'              => 'datetime',
        'login_attempts'            => 'integer',
    ];

    // ── Auth override (column is password_hash, not password) ────────────
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    // =========================================================================
    // Relations — Authentication Module
    // =========================================================================

    public function skills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    public function endorsementsGiven(): HasMany
    {
        return $this->hasMany(SkillEndorsement::class, 'endorsed_by_user_id');
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function passwordResets(): HasMany
    {
        return $this->hasMany(PasswordReset::class);
    }

    public function identityVerification(): HasOne
    {
        return $this->hasOne(IdentityVerification::class);
    }

    public function verificationAttempts(): HasMany
    {
        return $this->hasMany(VerificationAttempt::class);
    }

    /** Verifications this user reviewed (as moderator/admin) */
    public function verificationReviews(): HasMany
    {
        return $this->hasMany(VerificationReview::class, 'reviewer_id');
    }

    // =========================================================================
    // Relations — Project Management Module
    // =========================================================================

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(ProjectTeamMember::class);
    }

    public function projectApplications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class, 'applicant_id');
    }

    /** Applications this user reviewed (as project owner) */
    public function reviewedApplications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class, 'reviewed_by');
    }

    // =========================================================================
    // Relations — Collaboration Module
    // =========================================================================

    public function sentConnectionRequests(): HasMany
    {
        return $this->hasMany(UserConnection::class, 'requester_id');
    }

    public function receivedConnectionRequests(): HasMany
    {
        return $this->hasMany(UserConnection::class, 'recipient_id');
    }

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(CollaborationInvitation::class, 'sender_id');
    }

    public function receivedInvitations(): HasMany
    {
        return $this->hasMany(CollaborationInvitation::class, 'recipient_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchModel::class);
    }

    public function matchFeedback(): HasMany
    {
        return $this->hasMany(MatchFeedback::class);
    }

    public function ratingsGiven(): HasMany
    {
        return $this->hasMany(CollaborationRating::class, 'rater_id');
    }

    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(CollaborationRating::class, 'rated_user_id');
    }

    // =========================================================================
    // Relations — Communication Module
    // =========================================================================

    public function createdConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'created_by');
    }

    public function conversationParticipations(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function messageReadReceipts(): HasMany
    {
        return $this->hasMany(MessageReadReceipt::class);
    }

    public function messageReactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(File::class, 'uploader_id');
    }

    public function sharedFiles(): HasMany
    {
        return $this->hasMany(SharedFile::class, 'shared_by');
    }

    public function initiatedCalls(): HasMany
    {
        return $this->hasMany(VideoCall::class, 'initiated_by');
    }

    public function callParticipations(): HasMany
    {
        return $this->hasMany(CallParticipant::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function notificationPreferences(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    // =========================================================================
    // Relations — Administration Module
    // =========================================================================

    public function adminActions(): HasMany
    {
        return $this->hasMany(AdminAction::class, 'admin_id');
    }

    public function reportsFiled(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function reportsReceived(): HasMany
    {
        return $this->hasMany(Report::class, 'reported_user_id');
    }

    public function assignedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'assigned_to');
    }

    public function resolvedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'resolved_by');
    }

    public function moderatedContent(): HasMany
    {
        return $this->hasMany(ContentModeration::class, 'moderator_id');
    }

    public function restrictions(): HasMany
    {
        return $this->hasMany(UserRestriction::class);
    }

    public function restrictionsIssued(): HasMany
    {
        return $this->hasMany(UserRestriction::class, 'restricted_by');
    }

    public function restrictionsLifted(): HasMany
    {
        return $this->hasMany(UserRestriction::class, 'lifted_by');
    }

    public function systemLogs(): HasMany
    {
        return $this->hasMany(SystemLog::class);
    }

    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class);
    }

    public function updatedSettings(): HasMany
    {
        return $this->hasMany(SystemSetting::class, 'updated_by');
    }

    public function configurationChanges(): HasMany
    {
        return $this->hasMany(ConfigurationHistory::class, 'changed_by');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isModerator(): bool
    {
        return in_array($this->role, ['admin', 'moderator']);
    }

    public function isActive(): bool
    {
        return $this->account_status === 'active';
    }

    public function isIdentityVerified(): bool
    {
        return (bool) $this->identity_verified;
    }
}
