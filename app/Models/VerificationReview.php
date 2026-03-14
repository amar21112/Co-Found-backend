<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationReview extends Model
{
    use HasFactory, HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'verification_id', 'reviewer_id', 'review_action',
        'review_notes', 'rejection_reason_category',
        'reviewed_at', 'automated_checks_passed', 'automated_checks_data',
    ];

    protected $casts = [
        'reviewed_at'              => 'datetime',
        'automated_checks_passed'  => 'boolean',
        'automated_checks_data'    => 'array',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(IdentityVerification::class, 'verification_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
