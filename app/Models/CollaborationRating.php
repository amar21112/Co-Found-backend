<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollaborationRating extends Model
{
    use HasFactory, HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'rater_id', 'rated_user_id', 'project_id',
        'communication_rating', 'reliability_rating', 'skill_rating',
        'problem_solving_rating', 'teamwork_rating', 'overall_rating',
        'written_feedback', 'visibility',
    ];

    protected $casts = [
        'communication_rating'   => 'integer',
        'reliability_rating'     => 'integer',
        'skill_rating'           => 'integer',
        'problem_solving_rating' => 'integer',
        'teamwork_rating'        => 'integer',
        'overall_rating'         => 'float',
        'created_at'             => 'datetime',
    ];

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function ratedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
