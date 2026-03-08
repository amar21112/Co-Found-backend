<?php

namespace Database\Seeders;

use App\Models\MessageReaction;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class MessageReactionSeeder extends Seeder
{
    public function run(): void
    {
        $messages = Message::all();

        foreach ($messages as $message) {
            $reactionCount = rand(0, 5);
            $conversation = $message->conversation;
            $participants = $conversation->participants()->with('user')->get()->pluck('user');

            for ($i = 0; $i < $reactionCount; $i++) {
                $user = $participants->random();

                MessageReaction::factory()
                    ->create([
                        'message_id' => $message->id,
                        'user_id' => $user->id
                    ]);
            }
        }
    }
}
