<?php

namespace App\Models;

use Database\Factories\CollaborationInvitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $sender_id
 * @property string $recipient_id
 * @property string|null $project_id
 * @property string $invitation_type
 * @property string|null $role
 * @property string|null $message
 * @property string $status
 * @property Carbon|null $expires_at
 * @property Carbon|null $responded_at
 * @property string|null $response_message
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|CollaborationInvitation create(array $attributes = [])
 * @method static Builder|CollaborationInvitation find($id, $columns = ['*'])
 * @method static Builder|CollaborationInvitation findOrFail($id, $columns = ['*'])
 * @method static Builder|CollaborationInvitation first($columns = ['*'])
 * @method static Builder|CollaborationInvitation firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|CollaborationInvitation firstOrFail($columns = ['*'])
 * @method static Builder|CollaborationInvitation newModelQuery()
 * @method static Builder|CollaborationInvitation newQuery()
 * @method static Builder|CollaborationInvitation query()
 * @method static Builder|CollaborationInvitation where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|CollaborationInvitation whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|CollaborationInvitation with($relations, $callback = null)
 *
 * @method static Builder|CollaborationInvitation whereId($value)
 * @method static Builder|CollaborationInvitation whereSenderId($value)
 * @method static Builder|CollaborationInvitation whereRecipientId($value)
 * @method static Builder|CollaborationInvitation whereProjectId($value)
 * @method static Builder|CollaborationInvitation whereInvitationType($value)
 * @method static Builder|CollaborationInvitation whereRole($value)
 * @method static Builder|CollaborationInvitation whereMessage($value)
 * @method static Builder|CollaborationInvitation whereStatus($value)
 * @method static Builder|CollaborationInvitation whereExpiresAt($value)
 * @method static Builder|CollaborationInvitation whereRespondedAt($value)
 * @method static Builder|CollaborationInvitation whereResponseMessage($value)
 * @method static Builder|CollaborationInvitation whereCreatedAt($value)
 * @method static Builder|CollaborationInvitation whereUpdatedAt($value)
 *
 * @property-read User $sender
 * @property-read User $recipient
 * @property-read Project $project
 *
 * @method static CollaborationInvitationFactory factory($count = null, $state = [])
 */
class CollaborationInvitation extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'sender_id', 'recipient_id', 'project_id',
        'invitation_type', 'role', 'message', 'status',
        'expires_at', 'responded_at', 'response_message',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isAccepted(): bool { return $this->status === 'accepted'; }
    public function isExpired(): bool  { return $this->expires_at?->isPast() && $this->isPending(); }
}
