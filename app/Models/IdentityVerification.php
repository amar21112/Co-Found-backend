<?php

namespace App\Models;

use App\Enums\IdentityVerificationStatus;
use Database\Factories\IdentityVerificationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $user_id
 * @property string $id_card_image_front
 * @property string|null $id_card_image_back
 * @property string|null $id_card_number
 * @property string $full_name_on_card
 * @property Carbon|null $date_of_birth
 * @property string|null $nationality
 * @property Carbon|null $expiry_date
 * @property string|null $submission_method
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $device_info
 * @property bool|null $liveness_check_passed
 * @property array|null $liveness_check_data
 * @property float|null $face_match_score
 * @property IdentityVerificationStatus $verification_status
 * @property string|null $rejection_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|IdentityVerification create(array $attributes = [])
 * @method static Builder|IdentityVerification find($id, $columns = ['*'])
 * @method static Builder|IdentityVerification findOrFail($id, $columns = ['*'])
 * @method static Builder|IdentityVerification first($columns = ['*'])
 * @method static Builder|IdentityVerification firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|IdentityVerification firstOrFail($columns = ['*'])
 * @method static Builder|IdentityVerification newModelQuery()
 * @method static Builder|IdentityVerification newQuery()
 * @method static Builder|IdentityVerification query()
 * @method static Builder|IdentityVerification where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|IdentityVerification whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|IdentityVerification with($relations, $callback = null)
 *
 * @method static Builder|IdentityVerification whereId($value)
 * @method static Builder|IdentityVerification whereUserId($value)
 * @method static Builder|IdentityVerification whereIdCardImageFront($value)
 * @method static Builder|IdentityVerification whereIdCardImageBack($value)
 * @method static Builder|IdentityVerification whereIdCardNumber($value)
 * @method static Builder|IdentityVerification whereFullNameOnCard($value)
 * @method static Builder|IdentityVerification whereDateOfBirth($value)
 * @method static Builder|IdentityVerification whereNationality($value)
 * @method static Builder|IdentityVerification whereExpiryDate($value)
 * @method static Builder|IdentityVerification whereSubmissionMethod($value)
 * @method static Builder|IdentityVerification whereIpAddress($value)
 * @method static Builder|IdentityVerification whereUserAgent($value)
 * @method static Builder|IdentityVerification whereDeviceInfo($value)
 * @method static Builder|IdentityVerification whereLivenessCheckPassed($value)
 * @method static Builder|IdentityVerification whereLivenessCheckData($value)
 * @method static Builder|IdentityVerification whereFaceMatchScore($value)
 * @method static Builder|IdentityVerification whereVerificationStatus($value)
 * @method static Builder|IdentityVerification whereRejectionReason($value)
 * @method static Builder|IdentityVerification whereCreatedAt($value)
 * @method static Builder|IdentityVerification whereUpdatedAt($value)
 *
 * @property-read User $user
 * @property-read Collection|VerificationReview[] $reviews
 * @property-read Collection|VerificationReview[] $latestReview
 *
 * @method static IdentityVerificationFactory factory($count = null, $state = [])
 */
class IdentityVerification extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'user_id', 'id_card_image_front', 'id_card_image_back',
        'id_card_number', 'full_name_on_card',
        'date_of_birth', 'nationality', 'expiry_date',
        'submission_method', 'ip_address', 'user_agent', 'device_info',
        'liveness_check_passed', 'liveness_check_data', 'face_match_score',
        'verification_status', 'rejection_reason',
    ];

    protected $casts = [
        'date_of_birth'         => 'date',
        'expiry_date'           => 'date',
        'liveness_check_passed' => 'boolean',
        'liveness_check_data'   => 'array',
        'face_match_score'      => 'float',
        'verification_status'   => IdentityVerificationStatus::class,
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

    public function isPending(): bool   {
        return $this->verification_status === IdentityVerificationStatus::Pending;
    }

    public function isVerified(): bool  {
        return $this->verification_status === IdentityVerificationStatus::Verified;
    }

    public function isRejected(): bool  {
        return $this->verification_status === IdentityVerificationStatus::Rejected;
    }

    public function isUnderReview(): bool {
        return $this->verification_status === IdentityVerificationStatus::UnderReview;
    }

    public function isEscalated(): bool {
        return $this->verification_status === IdentityVerificationStatus::Escalated;
    }
}
