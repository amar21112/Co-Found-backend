<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $setting_key
 * @property array|null $old_value
 * @property array|null $new_value
 * @property string|null $changed_by
 * @property string|null $change_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|ConfigurationHistory create(array $attributes = [])
 * @method static Builder|ConfigurationHistory find($id, $columns = ['*'])
 * @method static Builder|ConfigurationHistory findOrFail($id, $columns = ['*'])
 * @method static Builder|ConfigurationHistory first($columns = ['*'])
 * @method static Builder|ConfigurationHistory firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|ConfigurationHistory firstOrFail($columns = ['*'])
 * @method static Builder|ConfigurationHistory newModelQuery()
 * @method static Builder|ConfigurationHistory newQuery()
 * @method static Builder|ConfigurationHistory query()
 * @method static Builder|ConfigurationHistory where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|ConfigurationHistory whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|ConfigurationHistory with($relations, $callback = null)
 *
 * @method static Builder|ConfigurationHistory whereId($value)
 * @method static Builder|ConfigurationHistory whereSettingKey($value)
 * @method static Builder|ConfigurationHistory whereOldValue($value)
 * @method static Builder|ConfigurationHistory whereNewValue($value)
 * @method static Builder|ConfigurationHistory whereChangedBy($value)
 * @method static Builder|ConfigurationHistory whereChangeReason($value)
 * @method static Builder|ConfigurationHistory whereCreatedAt($value)
 * @method static Builder|ConfigurationHistory whereUpdatedAt($value)
 *
 * @property-read User $changedBy
 * @property-read SystemSetting $setting
 */
class ConfigurationHistory extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $table = 'configuration_history';

    protected $fillable = [
        'setting_key', 'old_value', 'new_value',
        'changed_by', 'change_reason',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(SystemSetting::class, 'setting_key', 'setting_key');
    }
}
