<?php

namespace App\Models;

use Database\Factories\UserSkillFactory;
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
 * @property string $user_id
 * @property string $skill_name
 * @property int $proficiency_level
 * @property float|null $years_experience
 * @property bool $is_approved
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|UserSkill create(array $attributes = [])
 * @method static Builder|UserSkill find($id, $columns = ['*'])
 * @method static Builder|UserSkill findOrFail($id, $columns = ['*'])
 * @method static Builder|UserSkill first($columns = ['*'])
 * @method static Builder|UserSkill firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|UserSkill firstOrFail($columns = ['*'])
 * @method static Builder|UserSkill newModelQuery()
 * @method static Builder|UserSkill newQuery()
 * @method static Builder|UserSkill query()
 * @method static Builder|UserSkill where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|UserSkill whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|UserSkill with($relations, $callback = null)
 *
 * @method static Builder|UserSkill whereId($value)
 * @method static Builder|UserSkill whereUserId($value)
 * @method static Builder|UserSkill whereSkillName($value)
 * @method static Builder|UserSkill whereProficiencyLevel($value)
 * @method static Builder|UserSkill whereYearsExperience($value)
 * @method static Builder|UserSkill whereIsApproved($value)
 * @method static Builder|UserSkill whereCreatedAt($value)
 * @method static Builder|UserSkill whereUpdatedAt($value)
 *
 * @property-read User $user
 * @property-read Collection|SkillEndorsement[] $endorsements
 *
 * @method static UserSkillFactory factory($count = null, $state = [])
 */
class UserSkill extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'user_id', 'skill_name', 'proficiency_level',
        'years_experience', 'is_approved',
    ];

    protected $casts = [
        'proficiency_level' => 'integer',
        'years_experience'  => 'float',
        'is_approved'       => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function endorsements(): HasMany
    {
        return $this->hasMany(SkillEndorsement::class);
    }
}
