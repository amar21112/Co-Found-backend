<?php

namespace App\Models;

use App\Enums\RejectionReasonCategory;
use App\Enums\ReviewAction;
use Database\Factories\VerificationReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $verification_id
 * @property string $reviewer_id
 * @property ReviewAction $review_action
 * @property string|null $review_notes
 * @property RejectionReasonCategory|null $rejection_reason_category
 * @property Carbon|null $reviewed_at
 * @property bool $automated_checks_passed
 * @property array|null $automated_checks_data
 *
 * @method static Builder|VerificationReview create(array $attributes = [])
 * @method static Builder|VerificationReview find($id, $columns = ['*'])
 * @method static Builder|VerificationReview findOrFail($id, $columns = ['*'])
 * @method static Builder|VerificationReview first($columns = ['*'])
 * @method static Builder|VerificationReview firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|VerificationReview firstOrFail($columns = ['*'])
 * @method static Builder|VerificationReview newModelQuery()
 * @method static Builder|VerificationReview newQuery()
 * @method static Builder|VerificationReview query()
 * @method static Builder|VerificationReview where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|VerificationReview whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|VerificationReview with($relations, $callback = null)
 *
 * @method static Builder|VerificationReview whereId($value)
 * @method static Builder|VerificationReview whereVerificationId($value)
 * @method static Builder|VerificationReview whereReviewerId($value)
 * @method static Builder|VerificationReview whereReviewAction($value)
 * @method static Builder|VerificationReview whereReviewNotes($value)
 * @method static Builder|VerificationReview whereRejectionReasonCategory($value)
 * @method static Builder|VerificationReview whereReviewedAt($value)
 * @method static Builder|VerificationReview whereAutomatedChecksPassed($value)
 * @method static Builder|VerificationReview whereAutomatedChecksData($value)
 *
 * @property-read IdentityVerification $verification
 * @property-read User $reviewer
 *
 * @method static VerificationReviewFactory factory($count = null, $state = [])
 */
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
        'review_action' => ReviewAction::class,
        'rejection_reason_category' => RejectionReasonCategory::class,
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
