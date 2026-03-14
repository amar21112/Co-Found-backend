<?php

namespace Database\Factories;

use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IdentityVerificationFactory extends Factory
{
    protected $model = IdentityVerification::class;

    public function definition(): array
    {
        return [
            'id'                    => $this->faker->uuid(),
            'user_id'               => User::factory(),
            'id_card_image_front'   => 'verifications/' . $this->faker->uuid() . '_front.jpg',
            'id_card_image_back'    => 'verifications/' . $this->faker->uuid() . '_back.jpg',
            'id_card_number'        => hash('sha256', $this->faker->numerify('##########')),
            'full_name_on_card'     => $this->faker->name(),
            'date_of_birth'         => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'nationality'           => $this->faker->country(),
            'expiry_date'           => $this->faker->dateTimeBetween('+1 year', '+10 years')->format('Y-m-d'),
            'submission_method'     => $this->faker->randomElement([ 'mobile_capture', 'webcam']),
            'ip_address'            => $this->faker->ipv4(),
            'user_agent'            => $this->faker->userAgent(),
            'device_info'           => json_encode(['platform' => $this->faker->randomElement(['iOS', 'Android', 'Windows', 'macOS'])]),
            'liveness_check_passed' => true,
            'liveness_check_data'   => json_encode(['score' => $this->faker->randomFloat(2, 0.85, 1.0), 'checks' => ['face_detected', 'blink_detected']]),
            'verification_status'   => 'pending',
            'rejection_reason'      => null,
            'created_at'          => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn() => ['verification_status' => 'verified']);
    }

    public function rejected(): static
    {
        return $this->state(fn() => [
            'verification_status' => 'rejected',
            'rejection_reason'    => $this->faker->sentence(),
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn() => ['verification_status' => 'under_review']);
    }
}
