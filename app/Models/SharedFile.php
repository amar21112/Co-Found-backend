<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $file_id
 * @property string $conversation_id
 * @property string|null $message_id
 * @property string $shared_by
 * @property string $permission_level
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|SharedFile create(array $attributes = [])
 * @method static Builder|SharedFile find($id, $columns = ['*'])
 * @method static Builder|SharedFile findOrFail($id, $columns = ['*'])
 * @method static Builder|SharedFile first($columns = ['*'])
 * @method static Builder|SharedFile firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|SharedFile firstOrFail($columns = ['*'])
 * @method static Builder|SharedFile newModelQuery()
 * @method static Builder|SharedFile newQuery()
 * @method static Builder|SharedFile query()
 * @method static Builder|SharedFile where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|SharedFile whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|SharedFile with($relations, $callback = null)
 *
 * @method static Builder|SharedFile whereId($value)
 * @method static Builder|SharedFile whereFileId($value)
 * @method static Builder|SharedFile whereConversationId($value)
 * @method static Builder|SharedFile whereMessageId($value)
 * @method static Builder|SharedFile whereSharedBy($value)
 * @method static Builder|SharedFile wherePermissionLevel($value)
 * @method static Builder|SharedFile whereExpiresAt($value)
 * @method static Builder|SharedFile whereCreatedAt($value)
 * @method static Builder|SharedFile whereUpdatedAt($value)
 *
 * @property-read File $file
 * @property-read Conversation $conversation
 * @property-read Message|null $message
 * @property-read User $sharedBy
 */
class SharedFile extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'file_id', 'conversation_id', 'message_id',
        'shared_by', 'permission_level', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function isExpired(): bool { return $this->expires_at && $this->expires_at->isPast(); }
}
