<?php

namespace Database\Factories;

use App\Models\MessageReaction;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MessageReactionFactory extends Factory
{
    protected $model = MessageReaction::class;

    public function definition(): array
    {
        $reactions = ['👍', '❤️', '😂', '😮', '🎉', '🚀', '👏', '🔥'];

        return [
            'id' => Str::uuid(),
            'message_id' => Message::factory(),
            'user_id' => User::factory(),
            'reaction' => $this->faker->randomElement($reactions),
            'created_at' => $this->faker->dateTimeBetween('-2 days', 'now'),
        ];
    }
}
