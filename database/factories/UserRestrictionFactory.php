<?php

namespace Database\Factories;

use App\Models\UserRestriction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserRestrictionFactory extends Factory
{
    protected $model = UserRestriction::class;

    public function definition(): array
    {
        $types = ['warning', 'suspension', 'ban', 'content_restriction'];
        $type = $this->faker->randomElement($types);

        $startsAt = $this->faker->dateTimeBetween('-3 months', 'now');
        $durationHours = $type === 'suspension' ? $this->faker->numberBetween(24, 168) : null;
        $expiresAt = $durationHours ? (clone $startsAt)->modify("+{$durationHours} hours") : null;

        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'restricted_by' => User::factory(),
            'restriction_type' => $type,
            'reason' => $this->faker->paragraph(),
            'duration_hours' => $durationHours,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'is_active' => $expiresAt ? $expiresAt > now() : $this->faker->boolean(70),
            'lifted_by' => $this->faker->optional(0.1)->randomElement([User::factory()]),
            'lifted_at' => function (array $attributes) {
                return $attributes['lifted_by'] ? $this->faker->dateTimeBetween($attributes['starts_at'], 'now') : null;
            },
            'created_at' => $startsAt,
        ];
    }

    public function warning(): static
    {
        return $this->state([
            'restriction_type' => 'warning',
            'duration_hours' => null,
            'expires_at' => null,
        ]);
    }

    public function suspension(): static
    {
        $startsAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $durationHours = $this->faker->numberBetween(24, 168);
        $expiresAt = (clone $startsAt)->modify("+{$durationHours} hours");

        return $this->state([
            'restriction_type' => 'suspension',
            'duration_hours' => $durationHours,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'is_active' => $expiresAt > now(),
        ]);
    }

    public function ban(): static
    {
        return $this->state([
            'restriction_type' => 'ban',
            'duration_hours' => null,
            'expires_at' => null,
            'is_active' => true,
        ]);
    }

    public function active(): static
    {
        return $this->state([
            'is_active' => true,
            'lifted_by' => null,
            'lifted_at' => null,
        ]);
    }

    public function lifted(): static
    {
        return $this->state([
            'is_active' => false,
            'lifted_by' => User::factory(),
            'lifted_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
