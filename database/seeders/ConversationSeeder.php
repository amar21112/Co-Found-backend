<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('account_status', 'active')->get();
        $projects = Project::all();

        // Create direct conversations
        for ($i = 0; $i < 100; $i++) {
            $participants = $users->random(2);

            $conversation = Conversation::factory()
                ->direct()
                ->create([
                    'created_by' => $participants[0]->id,
                    'last_message_at' => now()->subHours(rand(1, 72))
                ]);

            foreach ($participants as $user) {
                ConversationParticipant::factory()
                    ->active()
                    ->create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $user->id,
                        'joined_at' => now()->subDays(rand(1, 30))
                    ]);
            }
        }

        // Create group conversations for projects
        foreach ($projects->random(min(30, $projects->count())) as $project) {
            $members = $project->teamMembers()
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter();

            if ($members->count() >= 2) {
                $conversation = Conversation::factory()
                    ->group()
                    ->forProject($project)
                    ->create([
                        'title' => $project->title . ' Team Chat',
                        'created_by' => $project->owner_id,
                        'last_message_at' => now()->subHours(rand(1, 48))
                    ]);

                foreach ($members as $user) {
                    $isAdmin = $user->id === $project->owner_id;

                    ConversationParticipant::factory()
                        ->active()
                        ->when($isAdmin, fn($factory) => $factory->admin())
                        ->create([
                            'conversation_id' => $conversation->id,
                            'user_id' => $user->id,
                            'joined_at' => now()->subDays(rand(1, 60))
                        ]);
                }
            }
        }

        // Create general group conversations
        for ($i = 0; $i < 20; $i++) {
            $creator = $users->random();

            $conversation = Conversation::factory()
                ->group()
                ->create([
                    'title' => $this->getGroupTitle($i),
                    'created_by' => $creator->id,
                    'last_message_at' => now()->subHours(rand(1, 72))
                ]);

            $participantCount = rand(3, 8);
            $participants = $users->random($participantCount);

            foreach ($participants as $user) {
                $isAdmin = $user->id === $creator->id;

                ConversationParticipant::factory()
                    ->active()
                    ->when($isAdmin, fn($factory) => $factory->admin())
                    ->when(rand(0, 4) === 0, fn($factory) => $factory->muted())
                    ->create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $user->id,
                        'joined_at' => now()->subDays(rand(1, 30))
                    ]);
            }
        }
    }

    private function getGroupTitle($index)
    {
        $titles = [
            'General Discussion',
            'Tech Talk',
            'Design Critique',
            'Project Ideas',
            'Open Source Contributors',
            'Remote Work Tips',
            'Career Growth',
            'Networking Events',
            'Learning Resources',
            'Industry News'
        ];

        return $titles[$index % count($titles)];
    }
}
