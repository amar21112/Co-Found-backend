<?php

namespace Database\Factories;

use App\Models\CollaborationInvitation;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CollaborationInvitationFactory extends Factory
{
    protected $model = CollaborationInvitation::class;

    public function definition(): array
    {
        $types = ['project_join', 'team_invite', 'collaboration_request', 'mentorship'];
        $statuses = ['pending', 'accepted', 'declined', 'expired', 'withdrawn'];

        return [
            'id' => Str::uuid(),
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'project_id' => $this->faker->optional(0.5)->randomElement([Project::factory()]),
            'invitation_type' => $this->faker->randomElement($types),
            'role' => $this->faker->optional(0.7)->jobTitle(),
            'message' => $this->faker->optional(0.8)->paragraph(),
            'status' => $this->faker->randomElement($statuses),
            'expires_at' => $this->faker->optional(0.7)->dateTimeBetween('+1 day', '+2 weeks'),
            'responded_at' => function (array $attributes) {
                return in_array($attributes['status'], ['accepted', 'declined'])
                    ? $this->faker->dateTimeBetween('-2 weeks', 'now')
                    : null;
            },
            'response_message' => function (array $attributes) {
                return $attributes['responded_at'] ? $this->faker->optional(0.5)->sentence() : null;
            },
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'responded_at' => null,
            'response_message' => null,
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+1 week'),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'responded_at' => $this->faker->dateTimeBetween('-2 weeks', 'now'),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
            'responded_at' => $this->faker->dateTimeBetween('-2 weeks', 'now'),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }
}
