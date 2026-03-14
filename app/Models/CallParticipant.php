<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallParticipant extends Model
{
    use HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'call_id', 'user_id', 'joined_at', 'left_at', 'duration_seconds', 'role',
    ];

    protected $casts = [
        'joined_at'        => 'datetime',
        'left_at'          => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(VideoCall::class, 'call_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isHost(): bool { return $this->role === 'host'; }
}
