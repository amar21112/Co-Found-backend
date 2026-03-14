<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'reporter_id', 'reported_user_id',
        'reported_content_type', 'reported_content_id',
        'report_type', 'description', 'evidence',
        'status', 'priority', 'assigned_to', 'resolved_by',
        'resolution_action', 'resolution_notes', 'resolved_at',
    ];

    protected $casts = [
        'evidence'    => 'array',
        'resolved_at' => 'datetime',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function assignedModerator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isResolved(): bool   { return $this->status === 'resolved'; }
    public function isCritical(): bool   { return $this->priority === 'critical'; }
}
