<?php

namespace App\Models;

use Database\Factories\ProjectRoleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $project_id
 * @property string $role_name
 * @property string|null $description
 * @property int $positions_needed
 * @property int $positions_filled
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|ProjectRole create(array $attributes = [])
 * @method static Builder|ProjectRole find($id, $columns = ['*'])
 * @method static Builder|ProjectRole findOrFail($id, $columns = ['*'])
 * @method static Builder|ProjectRole first($columns = ['*'])
 * @method static Builder|ProjectRole firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|ProjectRole firstOrFail($columns = ['*'])
 * @method static Builder|ProjectRole newModelQuery()
 * @method static Builder|ProjectRole newQuery()
 * @method static Builder|ProjectRole query()
 * @method static Builder|ProjectRole where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|ProjectRole whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|ProjectRole with($relations, $callback = null)
 *
 * @method static Builder|ProjectRole whereId($value)
 * @method static Builder|ProjectRole whereProjectId($value)
 * @method static Builder|ProjectRole whereRoleName($value)
 * @method static Builder|ProjectRole whereDescription($value)
 * @method static Builder|ProjectRole wherePositionsNeeded($value)
 * @method static Builder|ProjectRole wherePositionsFilled($value)
 * @method static Builder|ProjectRole whereCreatedAt($value)
 * @method static Builder|ProjectRole whereUpdatedAt($value)
 *
 * @property-read Project $project
 * @property-read Collection|ProjectApplication[] $applications
 * @property-read Collection|ProjectTeamMember[] $teamMembers
 *
 * @method static ProjectRoleFactory factory($count = null, $state = [])
 */
class ProjectRole extends Model
{
    use HasUuids, HasFactory;

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
