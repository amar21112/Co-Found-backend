<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSkill extends Model
{
    use HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'project_id', 'skill_name', 'proficiency_required',
        'positions_needed', 'positions_filled', 'is_required',
    ];

    protected $casts = [
        'proficiency_required' => 'integer',
        'positions_needed'     => 'integer',
        'positions_filled'     => 'integer',
        'is_required'          => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function hasOpenPositions(): bool
    {
        return $this->positions_filled < $this->positions_needed;
    }
}
