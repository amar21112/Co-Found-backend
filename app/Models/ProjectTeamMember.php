<?php

namespace App\Models;

use Database\Factories\ProjectTeamMemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $project_id
 * @property string $user_id
 * @property string|null $role_id
 * @property string $position
 * @property string $permissions
 * @property Carbon|null $joined_at
 * @property Carbon|null $left_at
 * @property bool $is_active
 *
 * @method static Builder|ProjectTeamMember create(array $attributes = [])
 * @method static Builder|ProjectTeamMember find($id, $columns = ['*'])
 * @method static Builder|ProjectTeamMember findOrFail($id, $columns = ['*'])
 * @method static Builder|ProjectTeamMember first($columns = ['*'])
 * @method static Builder|ProjectTeamMember firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|ProjectTeamMember firstOrFail($columns = ['*'])
 * @method static Builder|ProjectTeamMember newModelQuery()
 * @method static Builder|ProjectTeamMember newQuery()
 * @method static Builder|ProjectTeamMember query()
 * @method static Builder|ProjectTeamMember where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|ProjectTeamMember whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|ProjectTeamMember with($relations, $callback = null)
 *
 * @method static Builder|ProjectTeamMember whereId($value)
 * @method static Builder|ProjectTeamMember whereProjectId($value)
 * @method static Builder|ProjectTeamMember whereUserId($value)
 * @method static Builder|ProjectTeamMember whereRoleId($value)
 * @method static Builder|ProjectTeamMember wherePosition($value)
 * @method static Builder|ProjectTeamMember wherePermissions($value)
 * @method static Builder|ProjectTeamMember whereJoinedAt($value)
 * @method static Builder|ProjectTeamMember whereLeftAt($value)
 * @method static Builder|ProjectTeamMember whereIsActive($value)
 *
 * @property-read Project $project
 * @property-read User $user
 * @property-read ProjectRole|null $role
 *
 * @method static ProjectTeamMemberFactory factory($count = null, $state = [])
 */
class ProjectTeamMember extends Model
{
    use HasFactory, HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'project_id', 'user_id', 'role_id',
        'position', 'permissions', 'joined_at', 'left_at', 'is_active',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at'   => 'datetime',
        'is_active' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ProjectRole::class, 'role_id');
    }
}
