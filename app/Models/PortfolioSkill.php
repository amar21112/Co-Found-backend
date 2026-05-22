<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Builder;

/**
 * @property string $id
 * @property string $portfolio_item_id
 * @property string $skill_name
 *
 * @method static Builder|PortfolioSkill create(array $attributes = [])
 * @method static Builder|PortfolioSkill find($id, $columns = ['*'])
 * @method static Builder|PortfolioSkill findOrFail($id, $columns = ['*'])
 * @method static Builder|PortfolioSkill first($columns = ['*'])
 * @method static Builder|PortfolioSkill firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|PortfolioSkill firstOrFail($columns = ['*'])
 * @method static Builder|PortfolioSkill newModelQuery()
 * @method static Builder|PortfolioSkill newQuery()
 * @method static Builder|PortfolioSkill query()
 * @method static Builder|PortfolioSkill where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|PortfolioSkill whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|PortfolioSkill with($relations, $callback = null)
 *
 * @method static Builder|PortfolioSkill whereId($value)
 * @method static Builder|PortfolioSkill wherePortfolioItemId($value)
 * @method static Builder|PortfolioSkill whereSkillName($value)
 *
 * @property-read PortfolioItem $portfolioItem
 */
class PortfolioSkill extends Model
{
    use HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = ['portfolio_item_id', 'skill_name'];

    public function portfolioItem(): BelongsTo
    {
        return $this->belongsTo(PortfolioItem::class);
    }
}
