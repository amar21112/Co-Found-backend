<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRole extends Model
{
    use HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'project_id', 'role_name', 'description',
        'positions_needed', 'positions_filled',
    ];

    protected $casts = [
        'positions_needed' => 'integer',
        'positions_filled' => 'integer',
        'created_at'       => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class, 'role_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(ProjectTeamMember::class, 'role_id');
    }

    public function hasOpenPositions(): bool
    {
        return $this->positions_filled < $this->positions_needed;
    }
}
