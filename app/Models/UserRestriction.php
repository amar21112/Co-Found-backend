<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRestriction extends Model
{
    use HasFactory, HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'user_id', 'restricted_by', 'restriction_type', 'reason',
        'duration_hours', 'starts_at', 'expires_at',
        'is_active', 'lifted_by', 'lifted_at',
    ];

    protected $casts = [
        'starts_at'      => 'datetime',
        'expires_at'     => 'datetime',
        'lifted_at'      => 'datetime',
        'is_active'      => 'boolean',
        'duration_hours' => 'integer',
        'created_at'     => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restrictedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restricted_by');
    }

    public function liftedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }

    public function isPermanent(): bool { return $this->duration_hours === null; }
    public function isExpired(): bool   { return $this->expires_at?->isPast(); }
}
