<?php

namespace Database\Factories;

use App\Models\VerificationReview;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VerificationReviewFactory extends Factory
{
    protected $model = VerificationReview::class;

    public function definition(): array
    {
        $action = $this->faker->randomElement(['approved', 'rejected', 'request_resubmission']);

        return [
            'id'                       => $this->faker->uuid(),
            'verification_id'          => IdentityVerification::factory(),
            'reviewer_id'              => User::factory()->moderator(),
            'review_action'            => $action,
            'review_notes'             => $this->faker->sentence(),
            'rejection_reason_category'=> $action === 'rejected'
                ? $this->faker->randomElement(['forgery', 'expired', 'unclear', 'mismatch', 'other'])
                : null,
            'reviewed_at'              => $this->faker->dateTimeBetween('-7 days', 'now'),
            'automated_checks_passed'  => true,
            'automated_checks_data'    => json_encode([
                'ocr_passed'    => true,
                'face_match'    => $this->faker->randomFloat(2, 0.90, 1.0),
                'expiry_valid'  => true,
            ]),
        ];
    }
}
