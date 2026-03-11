<?php

namespace Database\Factories;

use App\Models\VerificationAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VerificationAttemptFactory extends Factory
{
    protected $model = VerificationAttempt::class;

    public function definition(): array
    {
        $results = ['success', 'failure', 'abandoned'];
        $failureReasons = [
            'Image too blurry',
            'Liveness check failed',
            'Face mismatch',
            'Network error',
            'User cancelled',
            'Timeout'
        ];

        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'attempt_number' => $this->faker->numberBetween(1, 5),
            'submission_data' => json_encode([
                'method' => $this->faker->randomElement(['upload', 'webcam']),
                'browser' => $this->faker->userAgent(),
                'timestamp' => $this->faker->iso8601()
            ]),
            'result' => $this->faker->randomElement($results),
            'failure_reason' => function (array $attributes) use ($failureReasons) {
                return $attributes['result'] === 'failure'
                    ? $this->faker->randomElement($failureReasons)
                    : null;
            },
            'ip_address' => $this->faker->ipv4(),
            'created_at' => $this->faker->dateTimeBetween('-60 days', 'now'),
        ];
    }

    public function success(): static
    {
        return $this->state([
            'result' => 'success',
            'failure_reason' => null,
        ]);
    }

    public function failure(): static
    {
        return $this->state([
            'result' => 'failure',
        ]);
    }
}
