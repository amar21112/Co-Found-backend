<?php

namespace Database\Factories;

use App\Models\AdminAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AdminActionFactory extends Factory
{
    protected $model = AdminAction::class;

    public function definition(): array
    {
        $actionTypes = [
            'user_suspended', 'user_banned', 'user_verified',
            'content_removed', 'project_featured', 'settings_changed',
            'report_resolved'
        ];

        return [
            'id' => Str::uuid(),
            'admin_id' => User::factory(),
            'action_type' => $this->faker->randomElement($actionTypes),
            'target_type' => $this->faker->randomElement(['user', 'project', 'content', 'report', 'setting']),
            'target_id' => Str::uuid(),
            'details' => [
                'reason' => $this->faker->sentence(),
                'duration' => $this->faker->optional(0.3)->numberBetween(1, 30),
                'notes' => $this->faker->optional(0.5)->paragraph(),
            ],
            'ip_address' => $this->faker->ipv4(),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function userAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'user',
            'action_type' => $this->faker->randomElement(['user_suspended', 'user_banned', 'user_verified']),
        ]);
    }

    public function contentAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'content',
            'action_type' => 'content_removed',
        ]);
    }

    public function reportAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'report',
            'action_type' => 'report_resolved',
        ]);
    }
}
