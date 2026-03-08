<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'system_settings';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'description',
        'is_public',
        'updated_by'
    ];

    protected $casts = [
        'setting_value' => 'array',
        'is_public' => 'boolean'
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function history()
    {
        return $this->hasMany(ConfigurationHistory::class, 'setting_key', 'setting_key');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('setting_key', $key);
    }

    public function getValueAttribute()
    {
        $value = $this->setting_value;

        switch ($this->setting_type) {
            case 'boolean':
                return (bool) $value;
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'string':
                return (string) $value;
            default:
                return $value;
        }
    }

    public function setValue($value, $userId = null)
    {
        $oldValue = $this->setting_value;

        $this->setting_value = $value;
        $this->updated_by = $userId;
        $this->save();

        // Record in history
        ConfigurationHistory::create([
            'setting_key' => $this->setting_key,
            'old_value' => $oldValue,
            'new_value' => $value,
            'changed_by' => $userId
        ]);
    }

    public static function get($key, $default = null)
    {
        $setting = self::where('setting_key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->value;
    }

    public static function set($key, $value, $userId = null)
    {
        $setting = self::firstOrCreate(
            ['setting_key' => $key],
            [
                'setting_type' => gettype($value),
                'description' => 'Auto-created setting'
            ]
        );

        $setting->setValue($value, $userId);

        return $setting;
    }
}
