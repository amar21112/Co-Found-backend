<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        $digests = ['immediate', 'hourly', 'daily', 'weekly', 'none'];

        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'platform_notifications' => $this->faker->boolean(90),
            'email_notifications' => $this->faker->boolean(80),
            'push_notifications' => $this->faker->boolean(70),
            'notification_digest' => $this->faker->randomElement($digests),
            'quiet_hours_start' => $this->faker->optional(0.3)->time('H:i', '22:00'),
            'quiet_hours_end' => $this->faker->optional(0.3)->time('H:i', '07:00'),
            'quiet_hours_timezone' => 'UTC',
            'preferences' => [
                'message' => $this->faker->boolean(90),
                'application_update' => $this->faker->boolean(85),
                'connection_request' => $this->faker->boolean(80),
                'project_update' => $this->faker->boolean(75),
                'mention' => $this->faker->boolean(95),
                'system' => $this->faker->boolean(70),
                'collaboration_invite' => $this->faker->boolean(85)
            ],
            'updated_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function allEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform_notifications' => true,
            'email_notifications' => true,
            'push_notifications' => true,
            'preferences' => [
                'message' => true,
                'application_update' => true,
                'connection_request' => true,
                'project_update' => true,
                'mention' => true,
                'system' => true,
                'collaboration_invite' => true
            ],
        ]);
    }

    public function quietHours(): static
    {
        return $this->state(fn (array $attributes) => [
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '07:00',
        ]);
    }

    public function digestDaily(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_digest' => 'daily',
        ]);
    }
}
