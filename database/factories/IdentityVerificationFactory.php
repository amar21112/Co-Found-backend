<?php

namespace Database\Factories;

use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class IdentityVerificationFactory extends Factory
{
    protected $model = IdentityVerification::class;

    public function definition(): array
    {
        $statuses = ['pending', 'under_review', 'verified', 'rejected', 'escalated'];
        $idTypes = ['passport', 'drivers_license', 'national_id'];
        $submissionMethods = ['upload', 'mobile_capture', 'webcam'];

        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'id_card_image_front' => 'verifications/' . $this->faker->uuid() . '-front.jpg',
            'id_card_image_back' => 'verifications/' . $this->faker->uuid() . '-back.jpg',
            '' => $this->faker->randomElement($idTypes),
            'id_card_number' => $this->faker->optional(0.8)->regexify('[A-Z0-9]{9}'),
            'full_name_on_card' => $this->faker->name(),
            'date_of_birth' => $this->faker->date('Y-m-d', '-18 years'),
            'nationality' => $this->faker->country(),
            'expiry_date' => $this->faker->optional(0.7)->dateTimeBetween('+1 year', '+10 years'),
            'submission_method' => $this->faker->randomElement($submissionMethods),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'device_info' => json_encode(['browser' => 'Chrome', 'os' => 'Windows']),
            'liveness_check_passed' => $this->faker->boolean(80),
            'liveness_check_data' => json_encode(['score' => $this->faker->randomFloat(2, 0.7, 1.0)]),
            'face_match_score' => $this->faker->optional(0.6)->randomFloat(2, 0.5, 1.0),
            'verification_status' => $this->faker->randomElement($statuses),
            'rejection_reason' => function (array $attributes) {
                return $attributes['verification_status'] === 'rejected'
                    ? $this->faker->sentence()
                    : null;
            },
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'verification_status' => 'pending',
            'liveness_check_passed' => true,
        ]);
    }

    public function underReview(): static
    {
        return $this->state([
            'verification_status' => 'under_review',
            'liveness_check_passed' => true,
        ]);
    }

    public function verified(): static
    {
        return $this->state([
            'verification_status' => 'verified',
            'liveness_check_passed' => true,
            'face_match_score' => $this->faker->randomFloat(2, 0.85, 1.0),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'verification_status' => 'rejected',
            'rejection_reason' => $this->faker->randomElement([
                'Image too blurry',
                'ID card not fully visible',
                'Face mismatch',
                'Expired ID',
                'Invalid ID type'
            ]),
        ]);
    }
}
