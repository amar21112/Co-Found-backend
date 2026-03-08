<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationSkill extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'application_skills';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'application_id',
        'skill_name',
        'proficiency_claimed'
    ];

    protected $casts = [
        'proficiency_claimed' => 'integer'
    ];

    public $timestamps = false;

    public function application()
    {
        return $this->belongsTo(ProjectApplication::class, 'application_id');
    }
}
