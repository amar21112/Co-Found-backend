<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationParticipantFactory extends Factory
{
    protected $model = ConversationParticipant::class;

    public function definition(): array
    {
        return [
            'id'              => $this->faker->uuid(),
            'conversation_id' => Conversation::factory(),
            'user_id'         => User::factory(),
            'joined_at'       => now(),
            'left_at'         => null,
            'is_admin'        => false,
            'muted'           => false,
            'muted_until'     => null,
        ];
    }

    // ── States ────────────────────────────────────────────────────────────────

    public function admin(): static
    {
        return $this->state(fn() => ['is_admin' => true]);
    }

    public function left(): static
    {
        return $this->state(fn() => [
            'left_at' => now()->subHour(),
        ]);
    }

    public function muted(): static
    {
        return $this->state(fn() => [
            'muted'       => true,
            'muted_until' => now()->addHour(),
        ]);
    }
}
