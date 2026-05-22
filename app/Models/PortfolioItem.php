<?php

namespace App\Models;

use Database\Factories\PortfolioItemFactory;
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
 * @property string $title
 * @property string|null $description
 * @property string|null $file_url
 * @property string|null $thumbnail_url
 * @property string $item_type
 * @property string|null $external_url
 * @property string $visibility
 * @property bool $is_featured
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|PortfolioItem create(array $attributes = [])
 * @method static Builder|PortfolioItem find($id, $columns = ['*'])
 * @method static Builder|PortfolioItem findOrFail($id, $columns = ['*'])
 * @method static Builder|PortfolioItem first($columns = ['*'])
 * @method static Builder|PortfolioItem firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|PortfolioItem firstOrFail($columns = ['*'])
 * @method static Builder|PortfolioItem newModelQuery()
 * @method static Builder|PortfolioItem newQuery()
 * @method static Builder|PortfolioItem query()
 * @method static Builder|PortfolioItem where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|PortfolioItem whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|PortfolioItem with($relations, $callback = null)
 *
 * @method static Builder|PortfolioItem whereId($value)
 * @method static Builder|PortfolioItem whereUserId($value)
 * @method static Builder|PortfolioItem whereTitle($value)
 * @method static Builder|PortfolioItem whereDescription($value)
 * @method static Builder|PortfolioItem whereFileUrl($value)
 * @method static Builder|PortfolioItem whereThumbnailUrl($value)
 * @method static Builder|PortfolioItem whereItemType($value)
 * @method static Builder|PortfolioItem whereExternalUrl($value)
 * @method static Builder|PortfolioItem whereVisibility($value)
 * @method static Builder|PortfolioItem whereIsFeatured($value)
 * @method static Builder|PortfolioItem whereCreatedAt($value)
 * @method static Builder|PortfolioItem whereUpdatedAt($value)
 *
 * @property-read User $user
 * @property-read Collection|PortfolioSkill[] $skills
 *
 * @method static PortfolioItemFactory factory($count = null, $state = [])
 */
class PortfolioItem extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'user_id', 'title', 'description', 'file_url',
        'thumbnail_url', 'item_type', 'external_url',
        'visibility', 'is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(PortfolioSkill::class);
    }
}
