<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $application_id
 * @property string $skill_name
 * @property int|null $proficiency_claimed
 *
 * @method static Builder|ApplicationSkill create(array $attributes = [])
 * @method static Builder|ApplicationSkill find($id, $columns = ['*'])
 * @method static Builder|ApplicationSkill findOrFail($id, $columns = ['*'])
 * @method static Builder|ApplicationSkill first($columns = ['*'])
 * @method static Builder|ApplicationSkill firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|ApplicationSkill firstOrFail($columns = ['*'])
 * @method static Builder|ApplicationSkill newModelQuery()
 * @method static Builder|ApplicationSkill newQuery()
 * @method static Builder|ApplicationSkill query()
 * @method static Builder|ApplicationSkill where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|ApplicationSkill whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|ApplicationSkill with($relations, $callback = null)
 *
 * @method static Builder|ApplicationSkill whereId($value)
 * @method static Builder|ApplicationSkill whereApplicationId($value)
 * @method static Builder|ApplicationSkill whereSkillName($value)
 * @method static Builder|ApplicationSkill whereProficiencyClaimed($value)
 *
 * @property-read ProjectApplication $application
 */
class ApplicationSkill extends Model
{
    use HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = ['application_id', 'skill_name', 'proficiency_claimed'];

    protected $casts = [
        'proficiency_claimed' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class, 'application_id');
    }
}
