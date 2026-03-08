<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigurationHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'configuration_history';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'setting_key',
        'old_value',
        'new_value',
        'changed_by',
        'change_reason'
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array'
    ];

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function setting()
    {
        return $this->belongsTo(SystemSetting::class, 'setting_key', 'setting_key');
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('setting_key', $key);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('changed_by', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function getChangesAttribute()
    {
        $old = is_array($this->old_value) ? $this->old_value : [$this->old_value];
        $new = is_array($this->new_value) ? $this->new_value : [$this->new_value];

        return [
            'old' => $old,
            'new' => $new
        ];
    }
}
