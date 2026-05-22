<?php

namespace App\Models;

use Database\Factories\SystemSettingFactory;
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
 * @property string $setting_key
 * @property array|null $setting_value
 * @property string $setting_type
 * @property string|null $description
 * @property bool $is_public
 * @property string|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|SystemSetting create(array $attributes = [])
 * @method static Builder|SystemSetting find($id, $columns = ['*'])
 * @method static Builder|SystemSetting findOrFail($id, $columns = ['*'])
 * @method static Builder|SystemSetting first($columns = ['*'])
 * @method static Builder|SystemSetting firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|SystemSetting firstOrFail($columns = ['*'])
 * @method static Builder|SystemSetting newModelQuery()
 * @method static Builder|SystemSetting newQuery()
 * @method static Builder|SystemSetting query()
 * @method static Builder|SystemSetting where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|SystemSetting whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|SystemSetting with($relations, $callback = null)
 *
 * @method static Builder|SystemSetting whereId($value)
 * @method static Builder|SystemSetting whereSettingKey($value)
 * @method static Builder|SystemSetting whereSettingValue($value)
 * @method static Builder|SystemSetting whereSettingType($value)
 * @method static Builder|SystemSetting whereDescription($value)
 * @method static Builder|SystemSetting whereIsPublic($value)
 * @method static Builder|SystemSetting whereUpdatedBy($value)
 * @method static Builder|SystemSetting whereCreatedAt($value)
 * @method static Builder|SystemSetting whereUpdatedAt($value)
 *
 * @property-read User|null $updatedBy
 * @property-read Collection|ConfigurationHistory[] $history
 *
 * @method static SystemSettingFactory factory($count = null, $state = [])
 */
class SystemSetting extends Model
{
    use HasUuids, HasFactory;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'setting_key', 'setting_value', 'setting_type',
        'description', 'is_public', 'updated_by',
    ];

    protected $casts = [
        'setting_value' => 'array',
        'is_public'     => 'boolean',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ConfigurationHistory::class, 'setting_key', 'setting_key');
    }
}
