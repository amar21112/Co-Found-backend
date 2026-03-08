<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillEndorsement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'skill_endorsements';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_skill_id',
        'endorsed_by_user_id'
    ];

    public function userSkill()
    {
        return $this->belongsTo(UserSkill::class, 'user_skill_id');
    }

    public function endorsedBy()
    {
        return $this->belongsTo(User::class, 'endorsed_by_user_id');
    }
}
