<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $reporter_id
 * @property string|null $reported_user_id
 * @property string|null $reported_content_type
 * @property string|null $reported_content_id
 * @property string $report_type
 * @property string|null $description
 * @property array|null $evidence
 * @property string $status
 * @property string $priority
 * @property string|null $assigned_to
 * @property string|null $resolved_by
 * @property string|null $resolution_action
 * @property string|null $resolution_notes
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|Report create(array $attributes = [])
 * @method static Builder|Report find($id, $columns = ['*'])
 * @method static Builder|Report findOrFail($id, $columns = ['*'])
 * @method static Builder|Report first($columns = ['*'])
 * @method static Builder|Report firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|Report firstOrFail($columns = ['*'])
 * @method static Builder|Report newModelQuery()
 * @method static Builder|Report newQuery()
 * @method static Builder|Report query()
 * @method static Builder|Report where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|Report whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|Report with($relations, $callback = null)
 *
 * @method static Builder|Report whereId($value)
 * @method static Builder|Report whereReporterId($value)
 * @method static Builder|Report whereReportedUserId($value)
 * @method static Builder|Report whereReportedContentType($value)
 * @method static Builder|Report whereReportedContentId($value)
 * @method static Builder|Report whereReportType($value)
 * @method static Builder|Report whereDescription($value)
 * @method static Builder|Report whereEvidence($value)
 * @method static Builder|Report whereStatus($value)
 * @method static Builder|Report wherePriority($value)
 * @method static Builder|Report whereAssignedTo($value)
 * @method static Builder|Report whereResolvedBy($value)
 * @method static Builder|Report whereResolutionAction($value)
 * @method static Builder|Report whereResolutionNotes($value)
 * @method static Builder|Report whereResolvedAt($value)
 * @method static Builder|Report whereCreatedAt($value)
 * @method static Builder|Report whereUpdatedAt($value)
 *
 * @property-read User $reporter
 * @property-read User|null $reportedUser
 * @property-read User|null $assignedModerator
 * @property-read User|null $resolver
 *
 * @method static ReportFactory factory($count = null, $state = [])
 */
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
    public function isHighPriority(): bool { return $this->priority === 'high'; }
}
