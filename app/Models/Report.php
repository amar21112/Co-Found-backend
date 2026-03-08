<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'reports';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'reported_content_type',
        'reported_content_id',
        'report_type',
        'description',
        'evidence',
        'status',
        'priority',
        'assigned_to',
        'resolved_by',
        'resolution_action',
        'resolution_notes',
        'resolved_at'
    ];

    protected $casts = [
        'evidence' => 'array',
        'resolved_at' => 'datetime'
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function reportedContent()
    {
        return $this->morphTo('reported_content', 'reported_content_type', 'reported_content_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeDismissed($query)
    {
        return $query->where('status', 'dismissed');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByReporter($query, $userId)
    {
        return $query->where('reporter_id', $userId);
    }

    public function scopeByReportedUser($query, $userId)
    {
        return $query->where('reported_user_id', $userId);
    }

    public function assignTo($userId)
    {
        $this->assigned_to = $userId;
        $this->status = 'under_review';
        $this->save();
    }

    public function resolve($action, $notes = null, $resolverId = null)
    {
        $this->status = 'resolved';
        $this->resolution_action = $action;
        $this->resolution_notes = $notes;
        $this->resolved_by = $resolverId;
        $this->resolved_at = now();
        $this->save();
    }

    public function dismiss($notes = null, $resolverId = null)
    {
        $this->status = 'dismissed';
        $this->resolution_notes = $notes;
        $this->resolved_by = $resolverId;
        $this->resolved_at = now();
        $this->save();
    }

    public function escalate()
    {
        $this->status = 'escalated';
        $this->priority = 'high';
        $this->save();
    }

    public function getReportedContentPreviewAttribute()
    {
        if (!$this->reported_content) {
            return null;
        }

        if (method_exists($this->reported_content, 'getPreviewAttribute')) {
            return $this->reported_content->preview;
        }

        return null;
    }
}
