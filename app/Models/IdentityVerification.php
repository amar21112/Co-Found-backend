<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdentityVerification extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'identity_verifications';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'id_card_image_front',
        'id_card_image_back',
        'id_card_type',
        'id_card_number',
        'full_name_on_card',
        'date_of_birth',
        'nationality',
        'expiry_date',
        'submission_method',
        'ip_address',
        'user_agent',
        'device_info',
        'liveness_check_passed',
        'liveness_check_data',
        'face_match_score',
        'verification_status',
        'rejection_reason'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'expiry_date' => 'date',
        'liveness_check_passed' => 'boolean',
        'liveness_check_data' => 'array',
        'face_match_score' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(VerificationReview::class, 'verification_id');
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('verification_status', 'under_review');
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopeRejected($query)
    {
        return $query->where('verification_status', 'rejected');
    }

    public function isVerified()
    {
        return $this->verification_status === 'verified';
    }

    public function isPending()
    {
        return $this->verification_status === 'pending';
    }

    public function markAsVerified($reviewerId = null)
    {
        $this->verification_status = 'verified';
        $this->save();

        if ($reviewerId) {
            $this->user()->update(['identity_verified' => true]);
        }
    }

    public function markAsRejected($reason, $reviewerId = null)
    {
        $this->verification_status = 'rejected';
        $this->rejection_reason = $reason;
        $this->save();
    }
}
