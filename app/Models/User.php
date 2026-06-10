<?php

namespace App\Models;

use App\Casts\ProfilePictureUrlCast;
use App\Enums\AccountStatus;
use App\Enums\IdentityVerificationLevel;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $id
 * @property string $email
 * @property string $username
 * @property string $password
 * @property string $full_name
 * @property string|null $profile_picture_url
 * @property string|null $bio
 * @property string|null $location
 * @property string|null $website_url
 * @property string|null $linkedin_url
 * @property string|null $github_url
 * @property UserRole $role
 * @property AccountStatus $account_status
 * @property bool $email_verified
 * @property bool $identity_verified
 * @property IdentityVerificationLevel $identity_verification_level
 * @property string|null $email_verification_token
 * @property Carbon|null $email_verification_expires
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property int $login_attempts
 * @property Carbon|null $locked_until
 * @property Carbon|null $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|User create(array $attributes = [])
 * @method static Builder|User find($id, $columns = ['*'])
 * @method static Builder|User findOrFail($id, $columns = ['*'])
 * @method static Builder|User first($columns = ['*'])
 * @method static Builder|User firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|User firstOrFail($columns = ['*'])
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|User whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|User with($relations, $callback = null)
 * @method static Builder|User withTrashed()
 * @method static Builder|User onlyTrashed()
 *
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereUsername($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User whereFullName($value)
 * @method static Builder|User whereProfilePictureUrl($value)
 * @method static Builder|User whereBio($value)
 * @method static Builder|User whereLocation($value)
 * @method static Builder|User whereWebsiteUrl($value)
 * @method static Builder|User whereLinkedinUrl($value)
 * @method static Builder|User whereGithubUrl($value)
 * @method static Builder|User whereRole($value)
 * @method static Builder|User whereAccountStatus($value)
 * @method static Builder|User whereEmailVerified($value)
 * @method static Builder|User whereIdentityVerified($value)
 * @method static Builder|User whereIdentityVerificationLevel($value)
 * @method static Builder|User whereEmailVerificationToken($value)
 * @method static Builder|User whereEmailVerificationExpires($value)
 * @method static Builder|User whereLastLoginAt($value)
 * @method static Builder|User whereLastLoginIp($value)
 * @method static Builder|User whereLoginAttempts($value)
 * @method static Builder|User whereLockedUntil($value)
 * @method static Builder|User whereDeletedAt($value)
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereUpdatedAt($value)
 *
 * @property-read Collection|UserSkill[] $skills
 * @property-read Collection|SkillEndorsement[] $endorsementsGiven
 * @property-read Collection|PortfolioItem[] $portfolioItems
 * @property-read Collection|Session[] $sessions
 * @property-read Collection|PasswordReset[] $passwordResets
 * @property-read IdentityVerification|null $identityVerification
 * @property-read Collection|VerificationAttempt[] $verificationAttempts
 * @property-read Collection|VerificationReview[] $verificationReviews
 * @property-read Collection|UserRestriction[] $restrictions
 * @property-read Collection|UserRestriction[] $activeRestrictions
 * @property-read Collection|Project[] $ownedProjects
 * @property-read Collection|ProjectTeamMember[] $teamMemberships
 * @property-read Collection|ProjectApplication[] $projectApplications
 * @property-read Collection|ProjectApplication[] $reviewedApplications
 * @property-read Collection|UserConnection[] $sentConnectionRequests
 * @property-read Collection|UserConnection[] $receivedConnectionRequests
 * @property-read Collection|CollaborationInvitation[] $sentInvitations
 * @property-read Collection|CollaborationInvitation[] $receivedInvitations
 * @property-read Collection|MatchModel[] $matches
 * @property-read Collection|MatchFeedback[] $matchFeedback
 * @property-read Collection|CollaborationRating[] $ratingsGiven
 * @property-read Collection|CollaborationRating[] $ratingsReceived
 * @property-read Collection|VideoCall[] $initiatedCalls
 * @property-read Collection|CallParticipant[] $callParticipations
 * @property-read Collection|Notification[] $notifications
 * @property-read NotificationPreference|null $notificationPreferences
 * @property-read Collection|AdminAction[] $adminActions
 * @property-read Collection|Report[] $reportsFiled
 * @property-read Collection|Report[] $reportsReceived
 * @property-read Collection|Report[] $assignedReports
 * @property-read Collection|Report[] $resolvedReports
 * @property-read Collection|ContentModeration[] $moderatedContent
 * @property-read Collection|UserRestriction[] $restrictionsIssued
 * @property-read Collection|UserRestriction[] $restrictionsLifted
 * @property-read Collection|SystemLog[] $systemLogs
 * @property-read Collection|AnalyticsEvent[] $analyticsEvents
 * @property-read Collection|SystemSetting[] $updatedSettings
 * @property-read Collection|ConfigurationHistory[] $configurationChanges
 *
 * @method static UserFactory factory($count = null, $state = [])
 */
class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes, HasApiTokens;

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
        'password', 'email_verification_token',
        'remember_token',
    ];

    protected $casts = [
        'profile_picture_url'         => ProfilePictureUrlCast::class,
        'role'                        => UserRole::class,
        'account_status'              => AccountStatus::class,
        'identity_verification_level' => IdentityVerificationLevel::class,
        'email_verified'              => 'boolean',
        'identity_verified'           => 'boolean',
        'email_verification_expires'  => 'datetime',
        'last_login_at'               => 'datetime',
        'locked_until'                => 'datetime',
        'login_attempts'              => 'integer',
    ];

    // ── Auth override ────────────────────────────────────────────────────────
    public function getAuthPassword(): string
    {
        return $this->password;
    }

    // =========================================================================
    // Role helpers
    // =========================================================================

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    public function isModerator(): bool
    {
        return $this->role->isModerator();
    }

    public function isGuest(): bool
    {
        return $this->role === UserRole::Guest;
    }

    public function isRegularUser(): bool
    {
        return $this->role === UserRole::RegularUser;
    }

    // =========================================================================
    // Account status helpers
    // =========================================================================

    public function isActive(): bool
    {
        return $this->account_status === AccountStatus::Active;
    }

    public function isPending(): bool
    {
        return $this->account_status === AccountStatus::Pending;
    }

    public function isSuspended(): bool
    {
        return $this->account_status === AccountStatus::Suspended;
    }

    public function isBanned(): bool
    {
        return $this->account_status === AccountStatus::Banned;
    }

    /**
     * True when the account is blocked from authenticating by an admin action.
     */
    public function isBlocked(): bool
    {
        return $this->account_status->isBlocked();
    }

    /**
     * True when the account can receive a Sanctum token.
     * Pending users can log in but are soft-blocked on write routes.
     */
    public function canAuthenticate(): bool
    {
        return $this->account_status->canAuthenticate();
    }

    // =========================================================================
    // Verification helpers
    // =========================================================================

    public function isEmailVerified(): bool
    {
        return $this->email_verified;
    }

    public function isIdentityVerified(): bool
    {
        return $this->identity_verified;
    }

    public function isFullyVerified(): bool
    {
        return $this->isEmailVerified() && $this->isIdentityVerified();
    }

    /**
     * Is the account temporarily locked due to brute-force?
     */
    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
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

    public function restrictions(): HasMany
    {
        return $this->hasMany(UserRestriction::class);
    }

    /** Active admin-issued restrictions */
    public function activeRestrictions(): HasMany
    {
        return $this->hasMany(UserRestriction::class)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
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
}
