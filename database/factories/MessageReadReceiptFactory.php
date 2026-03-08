<?php

namespace Database\Factories;

use App\Models\MessageReadReceipt;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MessageReadReceiptFactory extends Factory
{
    protected $model = MessageReadReceipt::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'message_id' => Message::factory(),
            'user_id' => User::factory(),
            'read_at' => $this->faker->dateTimeBetween('-2 days', 'now'),
        ];
    }
}
