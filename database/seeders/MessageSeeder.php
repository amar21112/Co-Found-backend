<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        $conversations = Conversation::all();

        foreach ($conversations as $conversation) {
            $messageCount = rand(5, 50);
            $participants = $conversation->participants()->with('user')->get()->pluck('user');

            for ($i = 0; $i < $messageCount; $i++) {
                $sender = $participants->random();
                $createdAt = $conversation->created_at->addMinutes($i * rand(5, 60));

                $message = Message::factory()
                    ->text()
                    ->create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => $sender->id,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt
                    ]);

                // Add read receipts
                foreach ($participants as $participant) {
                    if ($participant->id !== $sender->id && rand(0, 1)) {
                        $message->markAsRead($participant->id);
                    }
                }

                // Add reactions sometimes
                if (rand(0, 3) === 0) {
                    $reactors = $participants->where('id', '!=', $sender->id)->random(rand(1, 3));

                    foreach ($reactors as $reactor) {
                        $message->reactions()->create([
                            'user_id' => $reactor->id,
                            'reaction' => $this->getRandomReaction()
                        ]);
                    }
                }
            }

            $conversation->updateLastMessage();
        }
    }

    private function getRandomReaction()
    {
        $reactions = ['👍', '❤️', '😂', '😮', '🎉', '🚀', '👏', '🔥'];
        return $reactions[array_rand($reactions)];
    }
}
