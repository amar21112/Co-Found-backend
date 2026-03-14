<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    protected static array $types = [
        'new_application', 'application_accepted', 'application_rejected',
        'new_message', 'new_connection_request', 'connection_accepted',
        'project_update', 'new_match', 'team_member_joined', 'milestone_due',
        'collaboration_rating', 'identity_verified',
    ];

    public function definition(): array
    {
        $read = $this->faker->boolean(60);

        return [
            'id'           => $this->faker->uuid(),
            'user_id'      => User::factory(),
            'type'         => $this->faker->randomElement(self::$types),
            'title'        => $this->faker->sentence(5),
            'body'         => $this->faker->sentence(12),
            'data'         => json_encode(['related_id' => $this->faker->uuid()]),
            'priority'     => $this->faker->randomElement(['low', 'normal', 'high']),
            'read'         => $read,
            'read_at'      => $read ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
            'delivered_at' => $this->faker->dateTimeBetween('-14 days', 'now'),
        ];
    }

    public function unread(): static
    {
        return $this->state(fn() => ['read' => false, 'read_at' => null]);
    }
}
