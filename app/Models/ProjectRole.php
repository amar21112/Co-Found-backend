<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectRole extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'project_roles';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'role_name',
        'description',
        'positions_needed',
        'positions_filled'
    ];

    protected $casts = [
        'positions_needed' => 'integer',
        'positions_filled' => 'integer'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function teamMembers()
    {
        return $this->hasMany(ProjectTeamMember::class, 'role_id');
    }

    public function applications()
    {
        return $this->hasMany(ProjectApplication::class, 'role_id');
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
