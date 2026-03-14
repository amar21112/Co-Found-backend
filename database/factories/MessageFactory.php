<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'id'                     => $this->faker->uuid(),
            'conversation_id'        => Conversation::factory(),
            'sender_id'              => User::factory(),
            'message_type'           => $this->faker->randomElement(['text', 'text', 'text', 'image', 'file', 'system']),
            'content'                => $this->faker->paragraph(),
            'formatted_content'      => null,
            'replied_to_message_id'  => null,
            'is_pinned'              => $this->faker->boolean(5),
            'is_edited'              => $this->faker->boolean(10),
            'deleted_at'             => null,
        ];
    }
}
