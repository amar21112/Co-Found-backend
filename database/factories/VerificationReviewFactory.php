<?php

namespace Database\Factories;

use App\Models\VerificationReview;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VerificationReviewFactory extends Factory
{
    protected $model = VerificationReview::class;

    public function definition(): array
    {
        $actions = ['approved', 'rejected', 'request_resubmission'];
        $rejectionCategories = ['forgery', 'expired', 'unclear', 'mismatch', 'other'];

        return [
            'id' => Str::uuid(),
            'verification_id' => IdentityVerification::factory(),
            'reviewer_id' => User::factory(),
            'review_action' => $this->faker->randomElement($actions),
            'review_notes' => $this->faker->optional(0.7)->sentence(),
            'rejection_reason_category' => function (array $attributes) use ($rejectionCategories) {
                return $attributes['review_action'] === 'rejected'
                    ? $this->faker->randomElement($rejectionCategories)
                    : null;
            },
            'reviewed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'automated_checks_passed' => $this->faker->boolean(90),
            'automated_checks_data' => json_encode([
                'face_match' => $this->faker->randomFloat(2, 0.7, 1.0),
                'document_valid' => $this->faker->boolean(95),
                'liveness_score' => $this->faker->randomFloat(2, 0.8, 1.0)
            ]),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'review_action' => 'approved',
            'automated_checks_passed' => true,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'review_action' => 'rejected',
            'rejection_reason_category' => $this->faker->randomElement(['forgery', 'expired', 'unclear', 'mismatch']),
        ]);
    }
}
