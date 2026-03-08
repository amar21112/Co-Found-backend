<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSkill extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_skills';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'skill_name',
        'proficiency_level',
        'years_experience',
        'is_approved'
    ];

    protected $casts = [
        'proficiency_level' => 'integer',
        'years_experience' => 'decimal:1',
        'is_approved' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function endorsements()
    {
        return $this->hasMany(SkillEndorsement::class, 'user_skill_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function getEndorsementCountAttribute()
    {
        return $this->endorsements()->count();
    }
}
