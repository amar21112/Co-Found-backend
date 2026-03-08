<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSkill extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'project_skills';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'skill_name',
        'proficiency_required',
        'positions_needed',
        'positions_filled',
        'is_required'
    ];

    protected $casts = [
        'proficiency_required' => 'integer',
        'positions_needed' => 'integer',
        'positions_filled' => 'integer',
        'is_required' => 'boolean'
    ];

    public $timestamps = false;

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function getPositionsRemainingAttribute()
    {
        return $this->positions_needed - $this->positions_filled;
    }

    public function isFilled()
    {
        return $this->positions_filled >= $this->positions_needed;
    }
}
