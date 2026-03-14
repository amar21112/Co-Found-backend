<?php

namespace Database\Factories;

use App\Models\UserRestriction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserRestrictionFactory extends Factory
{
    protected $model = UserRestriction::class;

    public function definition(): array
    {
        $durationHours = $this->faker->randomElement([1, 6, 24, 72, 168, 720, null]);
        $startsAt      = $this->faker->dateTimeBetween('-30 days', 'now');
        $expiresAt     = $durationHours ? (clone $startsAt)->modify("+{$durationHours} hours") : null;
        $isActive      = $expiresAt ? $expiresAt > now() : true;

        return [
            'id'               => $this->faker->uuid(),
            'user_id'          => User::factory(),
            'restricted_by'    => User::factory()->moderator(),
            'restriction_type' => $this->faker->randomElement(['messaging_ban', 'posting_ban', 'application_ban', 'full_suspension']),
            'reason'           => $this->faker->sentence(),
            'duration_hours'   => $durationHours,
            'starts_at'        => $startsAt,
            'expires_at'       => $expiresAt,
            'is_active'        => $isActive,
            'lifted_by'        => !$isActive && $this->faker->boolean(50) ? User::factory()->admin() : null,
            'lifted_at'        => !$isActive && $this->faker->boolean(50) ? $this->faker->dateTimeBetween($startsAt, 'now') : null,
        ];
    }
}
