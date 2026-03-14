<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
