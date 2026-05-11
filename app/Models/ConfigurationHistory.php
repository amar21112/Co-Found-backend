<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
