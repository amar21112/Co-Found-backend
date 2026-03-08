<?php

namespace Database\Seeders;

use App\Models\VideoCall;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class VideoCallSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('account_status', 'active')->get();
        $conversations = Conversation::all();
        $projects = Project::all();

        // Create direct video calls
        for ($i = 0; $i < 30; $i++) {
            $initiator = $users->random();
            $conversation = $conversations->where('conversation_type', 'direct')->random();

            $status = $this->getRandomStatus();

            $factory = VideoCall::factory();

            if ($status === 'scheduled') {
                $factory->scheduled();
            } elseif ($status === 'active') {
                $factory->active();
            } elseif ($status === 'ended') {
                $factory->ended();
            } elseif ($status === 'cancelled') {
                $factory->cancelled();
            }

            $factory->create([
                'call_type' => 'direct',
                'conversation_id' => $conversation->id,
                'initiated_by' => $initiator->id
            ]);
        }

        // Create group video calls
        for ($i = 0; $i < 20; $i++) {
            $initiator = $users->random();
            $conversation = $conversations->where('conversation_type', 'group')->random();
            $project = $projects->random();

            $status = $this->getRandomStatus();

            $factory = VideoCall::factory();

            if ($status === 'scheduled') {
                $factory->scheduled();
            } elseif ($status === 'active') {
                $factory->active();
            } elseif ($status === 'ended') {
                $factory->ended();
            } elseif ($status === 'cancelled') {
                $factory->cancelled();
            }

            $factory->create([
                'call_type' => 'group',
                'conversation_id' => $conversation->id,
                'project_id' => $project->id,
                'initiated_by' => $initiator->id
            ]);
        }
    }

    private function getRandomStatus()
    {
        $statuses = [
            'scheduled' => 30,
            'active' => 10,
            'ended' => 50,
            'cancelled' => 10
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $status;
            }
        }

        return 'ended';
    }
}
