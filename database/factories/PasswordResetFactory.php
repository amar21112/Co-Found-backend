<?php

namespace Database\Factories;

use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PasswordResetFactory extends Factory
{
    protected $model = PasswordReset::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'reset_token' => Str::random(60),
            'expires_at' => $this->faker->dateTimeBetween('now', '+24 hours'),
            'used_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 day', 'now'),
            'created_at' => $this->faker->dateTimeBetween('-2 days', 'now'),
        ];
    }

    public function expired(): static
    {
        return $this->state([
            'expires_at' => $this->faker->dateTimeBetween('-2 days', '-1 hour'),
            'used_at' => null,
        ]);
    }

    public function used(): static
    {
        return $this->state([
            'used_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    public function valid(): static
    {
        return $this->state([
            'expires_at' => $this->faker->dateTimeBetween('+1 hour', '+24 hours'),
            'used_at' => null,
        ]);
    }
}
