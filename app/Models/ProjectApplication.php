<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectApplication extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'project_applications';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'applicant_id',
        'role_id',
        'cover_message',
        'proposed_role',
        'availability',
        'status',
        'match_score',
        'reviewed_by',
        'reviewed_at',
        'applied_at'
    ];

    protected $casts = [
        'match_score' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'applied_at' => 'datetime'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function role()
    {
        return $this->belongsTo(ProjectRole::class, 'role_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function skills()
    {
        return $this->hasMany(ApplicationSkill::class, 'application_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReviewing($query)
    {
        return $query->where('status', 'reviewing');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByApplicant($query, $applicantId)
    {
        return $query->where('applicant_id', $applicantId);
    }

    public function accept($reviewerId = null)
    {
        $this->status = 'accepted';
        $this->reviewed_at = now();
        $this->reviewed_by = $reviewerId;
        $this->save();
    }

    public function reject($reviewerId = null)
    {
        $this->status = 'rejected';
        $this->reviewed_at = now();
        $this->reviewed_by = $reviewerId;
        $this->save();
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isAccepted()
    {
        return $this->status === 'accepted';
    }
}
