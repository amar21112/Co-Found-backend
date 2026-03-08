<?php

namespace Database\Seeders;

use App\Models\CallParticipant;
use App\Models\VideoCall;
use App\Models\User;
use Illuminate\Database\Seeder;

class CallParticipantSeeder extends Seeder
{
    public function run(): void
    {
        $calls = VideoCall::all();

        foreach ($calls as $call) {
            $participantCount = $call->call_type === 'direct' ? 2 : rand(3, 8);
            $conversation = $call->conversation;

            if ($conversation) {
                $users = $conversation->users;

                foreach ($users->random(min($participantCount, $users->count())) as $user) {
                    $factory = CallParticipant::factory();

                    if ($user->id === $call->initiated_by) {
                        $factory->host();
                    }

                    if ($call->status === 'ended') {
                        $factory->left();
                    } elseif ($call->status === 'active') {
                        $factory->present();
                    }

                    $factory->create([
                        'call_id' => $call->id,
                        'user_id' => $user->id
                    ]);
                }
            }
        }
    }
}
