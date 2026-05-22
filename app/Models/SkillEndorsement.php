<?php

namespace App\Models;

use Database\Factories\SkillEndorsementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_skill_id
 * @property string $endorsed_by_user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|SkillEndorsement create(array $attributes = [])
 * @method static Builder|SkillEndorsement find($id, $columns = ['*'])
 * @method static Builder|SkillEndorsement findOrFail($id, $columns = ['*'])
 * @method static Builder|SkillEndorsement first($columns = ['*'])
 * @method static Builder|SkillEndorsement firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|SkillEndorsement firstOrFail($columns = ['*'])
 * @method static Builder|SkillEndorsement newModelQuery()
 * @method static Builder|SkillEndorsement newQuery()
 * @method static Builder|SkillEndorsement query()
 * @method static Builder|SkillEndorsement where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|SkillEndorsement whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|SkillEndorsement with($relations, $callback = null)
 *
 * @method static Builder|SkillEndorsement whereId($value)
 * @method static Builder|SkillEndorsement whereUserSkillId($value)
 * @method static Builder|SkillEndorsement whereEndorsedByUserId($value)
 * @method static Builder|SkillEndorsement whereCreatedAt($value)
 * @method static Builder|SkillEndorsement whereUpdatedAt($value)
 *
 * @property-read UserSkill $userSkill
 * @property-read User $endorser
 *
 * @method static SkillEndorsementFactory factory($count = null, $state = [])
 */
class SkillEndorsement extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'user_skill_id', 'endorsed_by_user_id',
    ];


    public function userSkill(): BelongsTo
    {
        return $this->belongsTo(UserSkill::class);
    }

    public function endorser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorsed_by_user_id');
    }
}
