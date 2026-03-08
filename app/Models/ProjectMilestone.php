<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMilestone extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'project_milestones';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'due_date',
        'completed_date',
        'status',
        'order_index'
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_date' => 'date',
        'order_index' => 'integer'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDelayed($query)
    {
        return $query->where('status', 'delayed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'delayed']);
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isOverdue()
    {
        return $this->due_date && $this->due_date < now() && !$this->isCompleted();
    }

    public function markAsCompleted()
    {
        $this->status = 'completed';
        $this->completed_date = now();
        $this->save();
    }
}
