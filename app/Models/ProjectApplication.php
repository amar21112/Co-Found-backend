<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
