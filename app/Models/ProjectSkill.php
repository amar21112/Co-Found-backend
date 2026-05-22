<?php

namespace App\Models;

use Database\Factories\ProjectSkillFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $project_id
 * @property string $skill_name
 * @property int $proficiency_required
 * @property int $positions_needed
 * @property int $positions_filled
 * @property bool $is_required
 *
 * @method static Builder|ProjectSkill create(array $attributes = [])
 * @method static Builder|ProjectSkill find($id, $columns = ['*'])
 * @method static Builder|ProjectSkill findOrFail($id, $columns = ['*'])
 * @method static Builder|ProjectSkill first($columns = ['*'])
 * @method static Builder|ProjectSkill firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|ProjectSkill firstOrFail($columns = ['*'])
 * @method static Builder|ProjectSkill newModelQuery()
 * @method static Builder|ProjectSkill newQuery()
 * @method static Builder|ProjectSkill query()
 * @method static Builder|ProjectSkill where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|ProjectSkill whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|ProjectSkill with($relations, $callback = null)
 *
 * @method static Builder|ProjectSkill whereId($value)
 * @method static Builder|ProjectSkill whereProjectId($value)
 * @method static Builder|ProjectSkill whereSkillName($value)
 * @method static Builder|ProjectSkill whereProficiencyRequired($value)
 * @method static Builder|ProjectSkill wherePositionsNeeded($value)
 * @method static Builder|ProjectSkill wherePositionsFilled($value)
 * @method static Builder|ProjectSkill whereIsRequired($value)
 *
 * @property-read Project $project
 *
 * @method static ProjectSkillFactory factory($count = null, $state = [])
 */
class ProjectSkill extends Model
{
    use HasUuids, HasFactory;

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
