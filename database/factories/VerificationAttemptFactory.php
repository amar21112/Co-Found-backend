<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VerificationAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

class VerificationAttemptFactory extends Factory
{
    protected $model = VerificationAttempt::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'attempt_number'  => 1,
            'result'          => 'success',
            'failure_reason'  => null,
            'ip_address'      => $this->faker->ipv4(),
            'submission_data' => [],
        ];
    }

    public function failure(string $reason = 'unknown'): static
    {
        return $this->state(fn() => [
            'result'         => 'failure',
            'failure_reason' => $reason,
        ]);
    }
}
