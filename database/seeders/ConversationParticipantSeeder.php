<?php

namespace Database\Seeders;

use App\Models\ConversationParticipant;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConversationParticipantSeeder extends Seeder
{
    public function run(): void
    {
        $conversations = Conversation::all();
        $users = User::where('account_status', 'active')->get();

        foreach ($conversations as $conversation) {
            // Skip if already has participants
            if ($conversation->participants()->count() > 0) {
                continue;
            }

            if ($conversation->conversation_type === 'direct') {
                // Add two participants for direct conversations
                $participants = $users->random(2);
                foreach ($participants as $user) {
                    ConversationParticipant::factory()
                        ->active()
                        ->create([
                            'conversation_id' => $conversation->id,
                            'user_id' => $user->id,
                            'joined_at' => $conversation->created_at,
                            'is_admin' => $user->id === $conversation->created_by
                        ]);
                }
            } else {
                // Add multiple participants for group conversations
                $participantCount = rand(3, 8);
                $participants = $users->random($participantCount);

                foreach ($participants as $user) {
                    ConversationParticipant::factory()
                        ->active()
                        ->create([
                            'conversation_id' => $conversation->id,
                            'user_id' => $user->id,
                            'joined_at' => $conversation->created_at->addDays(rand(0, 10)),
                            'is_admin' => $user->id === $conversation->created_by,
                            'muted' => rand(0, 4) === 0
                        ]);
                }
            }
        }
    }
}
