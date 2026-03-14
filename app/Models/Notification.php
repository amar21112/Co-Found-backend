<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory, HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'user_id', 'type', 'title', 'body', 'data',
        'priority', 'read', 'read_at', 'delivered_at',
    ];

    protected $casts = [
        'data'         => 'array',
        'read'         => 'boolean',
        'read_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        $this->update(['read' => true, 'read_at' => now()]);
    }

    public function isUnread(): bool { return !$this->read; }
    public function isHigh(): bool   { return $this->priority === 'high'; }
}
