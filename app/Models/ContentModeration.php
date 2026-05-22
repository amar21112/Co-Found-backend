<?php

namespace App\Models;

use Database\Factories\ContentModerationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $moderator_id
 * @property string $content_type
 * @property string $content_id
 * @property string $moderation_type
 * @property string|null $original_content
 * @property string|null $moderated_content
 * @property string $action_taken
 * @property string|null $reason
 * @property string|null $guideline_referenced
 * @property Carbon $created_at
 *
 * @method static Builder|ContentModeration create(array $attributes = [])
 * @method static Builder|ContentModeration find($id, $columns = ['*'])
 * @method static Builder|ContentModeration findOrFail($id, $columns = ['*'])
 * @method static Builder|ContentModeration first($columns = ['*'])
 * @method static Builder|ContentModeration firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|ContentModeration firstOrFail($columns = ['*'])
 * @method static Builder|ContentModeration newModelQuery()
 * @method static Builder|ContentModeration newQuery()
 * @method static Builder|ContentModeration query()
 * @method static Builder|ContentModeration where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|ContentModeration whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|ContentModeration with($relations, $callback = null)
 *
 * @method static Builder|ContentModeration whereId($value)
 * @method static Builder|ContentModeration whereModeratorId($value)
 * @method static Builder|ContentModeration whereContentType($value)
 * @method static Builder|ContentModeration whereContentId($value)
 * @method static Builder|ContentModeration whereModerationType($value)
 * @method static Builder|ContentModeration whereOriginalContent($value)
 * @method static Builder|ContentModeration whereModeratedContent($value)
 * @method static Builder|ContentModeration whereActionTaken($value)
 * @method static Builder|ContentModeration whereReason($value)
 * @method static Builder|ContentModeration whereGuidelineReferenced($value)
 * @method static Builder|ContentModeration whereCreatedAt($value)
 *
 * @property-read User $moderator
 *
 * @method static ContentModerationFactory factory($count = null, $state = [])
 */
class ContentModeration extends Model
{
    use HasUuids, HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'content_moderation';

    protected $fillable = [
        'moderator_id', 'content_type', 'content_id',
        'moderation_type', 'original_content', 'moderated_content',
        'action_taken', 'reason', 'guideline_referenced',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class , 'moderator_id');
    }
}
