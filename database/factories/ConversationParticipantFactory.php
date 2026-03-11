<?php

namespace Database\Factories;

use App\Models\ConversationParticipant;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ConversationParticipantFactory extends Factory
{
    protected $model = ConversationParticipant::class;

    public function definition(): array
    {
        $joinedAt = $this->faker->dateTimeBetween('-6 months', 'now');
        $leftAt = $this->faker->optional(0.1)->dateTimeBetween($joinedAt, 'now');

        return [
            'id' => Str::uuid(),
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
            'joined_at' => $joinedAt,
            'left_at' => $leftAt,
            'is_admin' => $this->faker->boolean(20),
            'muted' => $this->faker->boolean(10),
            'muted_until' => function (array $attributes) {
                return $attributes['muted']
                    ? $this->faker->optional(0.5)->dateTimeBetween('+1 day', '+1 month')
                    : null;
            },
        ];
    }

    public function admin(): static
    {
        return $this->state([
            'is_admin' => true,
        ]);
    }

    public function active(): static
    {
        return $this->state([
            'left_at' => null,
        ]);
    }

    public function left(): static
    {
        return $this->state([
            'left_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    public function muted(): static
    {
        return $this->state([
            'muted' => true,
            'muted_until' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }
}
