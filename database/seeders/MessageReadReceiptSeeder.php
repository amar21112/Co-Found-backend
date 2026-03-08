<?php

namespace Database\Seeders;

use App\Models\MessageReadReceipt;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class MessageReadReceiptSeeder extends Seeder
{
    public function run(): void
    {
        $messages = Message::all();

        foreach ($messages as $message) {
            $conversation = $message->conversation;
            $participants = $conversation->participants()->with('user')->get()->pluck('user');

            foreach ($participants as $participant) {
                if ($participant->id !== $message->sender_id && rand(0, 1)) {
                    MessageReadReceipt::factory()
                        ->create([
                            'message_id' => $message->id,
                            'user_id' => $participant->id,
                            'read_at' => $message->created_at->addMinutes(rand(1, 120))
                        ]);
                }
            }
        }
    }
}
