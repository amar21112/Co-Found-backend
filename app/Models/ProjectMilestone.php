<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'project_id', 'title', 'description',
        'due_date', 'completed_date', 'status', 'order_index',
    ];

    protected $casts = [
        'due_date'       => 'date',
        'completed_date' => 'date',
        'order_index'    => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isOverdue(): bool   { return $this->due_date?->isPast() && !$this->isCompleted(); }
}
