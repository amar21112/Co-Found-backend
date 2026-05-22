<?php

namespace App\Models;

use Database\Factories\ProjectMilestoneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $project_id
 * @property string $title
 * @property string|null $description
 * @property Carbon|null $due_date
 * @property Carbon|null $completed_date
 * @property string $status
 * @property int $order_index
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|ProjectMilestone create(array $attributes = [])
 * @method static Builder|ProjectMilestone find($id, $columns = ['*'])
 * @method static Builder|ProjectMilestone findOrFail($id, $columns = ['*'])
 * @method static Builder|ProjectMilestone first($columns = ['*'])
 * @method static Builder|ProjectMilestone firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|ProjectMilestone firstOrFail($columns = ['*'])
 * @method static Builder|ProjectMilestone newModelQuery()
 * @method static Builder|ProjectMilestone newQuery()
 * @method static Builder|ProjectMilestone query()
 * @method static Builder|ProjectMilestone where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|ProjectMilestone whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|ProjectMilestone with($relations, $callback = null)
 *
 * @method static Builder|ProjectMilestone whereId($value)
 * @method static Builder|ProjectMilestone whereProjectId($value)
 * @method static Builder|ProjectMilestone whereTitle($value)
 * @method static Builder|ProjectMilestone whereDescription($value)
 * @method static Builder|ProjectMilestone whereDueDate($value)
 * @method static Builder|ProjectMilestone whereCompletedDate($value)
 * @method static Builder|ProjectMilestone whereStatus($value)
 * @method static Builder|ProjectMilestone whereOrderIndex($value)
 * @method static Builder|ProjectMilestone whereCreatedAt($value)
 * @method static Builder|ProjectMilestone whereUpdatedAt($value)
 *
 * @property-read Project $project
 *
 * @method static ProjectMilestoneFactory factory($count = null, $state = [])
 */
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
