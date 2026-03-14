<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\VideoCall;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommunicationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'regular_user')->inRandomOrder()->get();

        // Use DB::table to avoid SoftDeletes / global scopes on the Project model
        $projects = \DB::table('projects')->inRandomOrder()->take(10)->get();

        $this->seedDirectConversations($users);
        $this->seedProjectConversations($projects);
        $this->seedNotifications($users);
        $this->seedVideoCalls($users, $projects);

        $this->command->info('CommunicationSeeder: conversations, messages, notifications, and video calls seeded.');
    }

    private function seedDirectConversations($users): void
    {
        for ($i = 0; $i < 20; $i++) {
            $userA = $users->random();
            $userB = $users->where('id', '!=', $userA->id)->random();

            $convId = Str::uuid();
            \DB::table('conversations')->insert([
                'id'                => $convId,
                'conversation_type' => 'direct',
                'title'             => null,
                'project_id'        => null,
                'created_by'        => $userA->id,
                'created_at'        => now()->subDays(rand(1, 30)),
                'updated_at'        => now(),
                'last_message_at'   => now()->subMinutes(rand(1, 1440)),
            ]);

            foreach ([$userA->id, $userB->id] as $uid) {
                \DB::table('conversation_participants')->insertOrIgnore([
                    'id'              => Str::uuid(),
                    'conversation_id' => $convId,
                    'user_id'         => $uid,
                    'joined_at'       => now()->subDays(rand(1, 30)),
                    'is_admin'        => false,
                    'muted'           => false,
                ]);
            }

            $lastMsgTime = now()->subMinutes(rand(60, 2880));
            $lastMsgId   = null;
            $count       = rand(5, 20);

            for ($m = 0; $m < $count; $m++) {
                $msgId  = Str::uuid();
                $sender = rand(0, 1) ? $userA->id : $userB->id;
                \DB::table('messages')->insert([
                    'id'                    => $msgId,
                    'conversation_id'       => $convId,
                    'sender_id'             => $sender,
                    'message_type'          => 'text',
                    'content'               => fake()->sentence(rand(4, 20)),
                    'formatted_content'     => null,
                    'replied_to_message_id' => $lastMsgId && rand(0, 4) === 0 ? $lastMsgId : null,
                    'is_pinned'             => false,
                    'is_edited'             => (bool) rand(0, 9) === 0,
                    'created_at'            => $lastMsgTime->copy()->addMinutes($m * rand(1, 30)),
                    'updated_at'            => now(),
                    'deleted_at'            => null,
                ]);
                $lastMsgId = $msgId;
            }
        }
    }

    private function seedProjectConversations($projects): void
    {
        foreach ($projects as $project) {
            $convId  = Str::uuid();
            \DB::table('conversations')->insert([
                'id'                => $convId,
                'conversation_type' => 'project',
                'title'             => $project->title . ' – Team Chat',
                'project_id'        => $project->id,
                'created_by'        => $project->owner_id,
                'created_at'        => now()->subDays(rand(10, 60)),
                'updated_at'        => now(),
                'last_message_at'   => now()->subHours(rand(1, 48)),
            ]);

            $memberIds = \DB::table('project_team_members')
                ->where('project_id', $project->id)
                ->pluck('user_id');

            if ($memberIds->isEmpty()) {
                // Fall back to the owner only
                $memberIds = collect([$project->owner_id]);
            }

            foreach ($memberIds as $uid) {
                \DB::table('conversation_participants')->insertOrIgnore([
                    'id'              => Str::uuid(),
                    'conversation_id' => $convId,
                    'user_id'         => $uid,
                    'joined_at'       => now()->subDays(rand(1, 60)),
                    'is_admin'        => $uid === $project->owner_id,
                    'muted'           => false,
                ]);
            }

            $msgTime = now()->subDays(rand(5, 20));
            for ($m = 0; $m < rand(10, 30); $m++) {
                \DB::table('messages')->insert([
                    'id'                    => Str::uuid(),
                    'conversation_id'       => $convId,
                    'sender_id'             => $memberIds->random(),
                    'message_type'          => 'text',
                    'content'               => fake()->sentence(rand(4, 25)),
                    'formatted_content'     => null,
                    'replied_to_message_id' => null,
                    'is_pinned'             => false,
                    'is_edited'             => false,
                    'created_at'            => $msgTime->copy()->addMinutes($m * rand(10, 120)),
                    'updated_at'            => now(),
                    'deleted_at'            => null,
                ]);
            }
        }
    }

    private function seedNotifications($users): void
    {
        $types = [
            'new_application', 'application_accepted', 'application_rejected',
            'new_message', 'new_connection_request', 'connection_accepted',
            'project_update', 'new_match', 'team_member_joined', 'milestone_due',
            'collaboration_rating', 'identity_verified',
        ];

        foreach ($users->take(40) as $user) {
            $count = rand(7, 14);
            for ($i = 0; $i < $count; $i++) {
                $read = (bool) rand(0, 1);
                \DB::table('notifications')->insert([
                    'id'           => Str::uuid(),
                    'user_id'      => $user->id,
                    'type'         => fake()->randomElement($types),
                    'title'        => fake()->sentence(5),
                    'body'         => fake()->sentence(12),
                    'data'         => json_encode(['related_id' => Str::uuid()]),
                    'priority'     => fake()->randomElement(['low', 'normal', 'high']),
                    'read'         => $read,
                    'read_at'      => $read ? now()->subDays(rand(1, 7)) : null,
                    'delivered_at' => now()->subDays(rand(1, 14)),
                    'created_at'   => now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }

    private function seedVideoCalls($users, $projects): void
    {
        // 10 ended calls
        for ($i = 0; $i < 10; $i++) {
            $initiator = $users->random();
            $callId    = Str::uuid();
            $start     = now()->subDays(rand(1, 30));
            $duration  = rand(300, 7200);
            $end       = $start->copy()->addSeconds($duration);

            \DB::table('video_calls')->insert([
                'id'               => $callId,
                'call_type'        => fake()->randomElement(['direct', 'group']),
                'conversation_id'  => null,
                'project_id'       => $projects->isNotEmpty() ? $projects->random()->id : null,
                'initiated_by'     => $initiator->id,
                'room_name'        => 'room-' . Str::random(12),
                'room_url'         => 'https://meet.cofound.io/room-' . Str::random(12),
                'start_time'       => $start,
                'end_time'         => $end,
                'duration_seconds' => $duration,
                'status'           => 'ended',
                'recording_url'    => rand(0, 3) === 0 ? 'https://recordings.cofound.io/' . Str::uuid() : null,
                'created_at'       => $start,
            ]);

            $participants = $users->shuffle()->take(rand(2, 4));
            foreach ($participants as $p) {
                \DB::table('call_participants')->insertOrIgnore([
                    'id'               => Str::uuid(),
                    'call_id'          => $callId,
                    'user_id'          => $p->id,
                    'joined_at'        => $start,
                    'left_at'          => $end,
                    'duration_seconds' => $duration,
                    'role'             => $p->id === $initiator->id ? 'host' : 'participant',
                ]);
            }
        }

        // 5 scheduled upcoming calls
        for ($i = 0; $i < 5; $i++) {
            $initiator = $users->random();
            $start     = now()->addDays(rand(1, 7));
            \DB::table('video_calls')->insert([
                'id'               => Str::uuid(),
                'call_type'        => fake()->randomElement(['direct', 'group']),
                'conversation_id'  => null,
                'project_id'       => $projects->isNotEmpty() ? $projects->random()->id : null,
                'initiated_by'     => $initiator->id,
                'room_name'        => 'room-' . Str::random(12),
                'room_url'         => 'https://meet.cofound.io/room-' . Str::random(12),
                'start_time'       => $start,
                'end_time'         => null,
                'duration_seconds' => null,
                'status'           => 'scheduled',
                'recording_url'    => null,
                'created_at'       => now(),
            ]);
        }
    }
}
