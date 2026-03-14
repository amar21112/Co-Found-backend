<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'compatibility_score' => 'float',
        'match_reasons'       => 'array',
        'viewed'              => 'boolean',
        'saved'               => 'boolean',
        'action_taken'        => 'boolean',
        'viewed_at'           => 'datetime',
        'expires_at'          => 'datetime',
    ];

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
        return $this->hasMany(MatchFeedback::class);
    }

    public function isExpired(): bool      { return $this->expires_at?->isPast(); }
    public function isUserMatch(): bool    { return $this->match_type === 'user_to_user'; }
    public function isProjectMatch(): bool { return $this->match_type === 'user_to_project'; }
}
