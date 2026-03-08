<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchModel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'matches';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'matched_user_id',
        'matched_project_id',
        'match_type',
        'compatibility_score',
        'match_reasons',
        'viewed',
        'viewed_at',
        'saved',
        'action_taken',
        'expires_at'
    ];

    protected $casts = [
        'compatibility_score' => 'decimal:2',
        'match_reasons' => 'array',
        'viewed' => 'boolean',
        'viewed_at' => 'datetime',
        'saved' => 'boolean',
        'action_taken' => 'boolean',
        'expires_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function matchedUser()
    {
        return $this->belongsTo(User::class, 'matched_user_id');
    }

    public function matchedProject()
    {
        return $this->belongsTo(Project::class, 'matched_project_id');
    }

    public function feedback()
    {
        return $this->hasMany(MatchFeedback::class, 'match_id');
    }

    public function scopeUnviewed($query)
    {
        return $query->where('viewed', false);
    }

    public function scopeViewed($query)
    {
        return $query->where('viewed', true);
    }

    public function scopeSaved($query)
    {
        return $query->where('saved', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('match_type', $type);
    }

    public function scopeHighScore($query, $threshold = 80)
    {
        return $query->where('compatibility_score', '>=', $threshold / 100);
    }

    public function markAsViewed()
    {
        $this->viewed = true;
        $this->viewed_at = now();
        $this->save();
    }

    public function markAsSaved()
    {
        $this->saved = true;
        $this->save();
    }

    public function markActionTaken()
    {
        $this->action_taken = true;
        $this->save();
    }

    public function getScorePercentageAttribute()
    {
        return round($this->compatibility_score * 100);
    }
}
