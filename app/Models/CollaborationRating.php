<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollaborationRating extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'collaboration_ratings';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'rater_id',
        'rated_user_id',
        'project_id',
        'communication_rating',
        'reliability_rating',
        'skill_rating',
        'problem_solving_rating',
        'teamwork_rating',
        'overall_rating',
        'written_feedback',
        'visibility'
    ];

    protected $casts = [
        'communication_rating' => 'integer',
        'reliability_rating' => 'integer',
        'skill_rating' => 'integer',
        'problem_solving_rating' => 'integer',
        'teamwork_rating' => 'integer',
        'overall_rating' => 'decimal:2'
    ];

    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function ratedUser()
    {
        return $this->belongsTo(User::class, 'rated_user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeByRater($query, $raterId)
    {
        return $query->where('rater_id', $raterId);
    }

    public function scopeByRatedUser($query, $ratedUserId)
    {
        return $query->where('rated_user_id', $ratedUserId);
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeHighRating($query, $threshold = 4)
    {
        return $query->where('overall_rating', '>=', $threshold);
    }

    public function calculateOverall()
    {
        $ratings = [
            $this->communication_rating,
            $this->reliability_rating,
            $this->skill_rating,
            $this->problem_solving_rating,
            $this->teamwork_rating
        ];

        $filtered = array_filter($ratings);

        if (empty($filtered)) {
            return null;
        }

        $this->overall_rating = array_sum($filtered) / count($filtered);
        return $this->overall_rating;
    }
}
