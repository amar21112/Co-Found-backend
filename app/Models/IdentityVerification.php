<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdentityVerification extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'user_id', 'id_card_image_front', 'id_card_image_back',
        'id_card_type', 'id_card_number', 'full_name_on_card',
        'date_of_birth', 'nationality', 'expiry_date',
        'submission_method', 'ip_address', 'user_agent', 'device_info',
        'liveness_check_passed', 'liveness_check_data',
        'verification_status', 'rejection_reason',
    ];

    protected $casts = [
        'date_of_birth'         => 'date',
        'expiry_date'           => 'date',
        'liveness_check_passed' => 'boolean',
        'liveness_check_data'   => 'array',
        'submitted_at'          => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VerificationReview::class, 'verification_id');
    }

    public function latestReview(): HasMany
    {
        return $this->hasMany(VerificationReview::class, 'verification_id')
                    ->latest('reviewed_at')
                    ->limit(1);
    }

    public function isPending(): bool   { return $this->verification_status === 'pending'; }
    public function isVerified(): bool  { return $this->verification_status === 'verified'; }
    public function isRejected(): bool  { return $this->verification_status === 'rejected'; }
}
