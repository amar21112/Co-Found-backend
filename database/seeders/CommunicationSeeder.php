<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommunicationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'regular_user')->inRandomOrder()->get();

        // Use DB::table to avoid SoftDeletes / global scopes on the Project model
        $projects = DB::table('projects')->inRandomOrder()->take(10)->get();

        $this->seedNotifications($users);
        $this->seedVideoCalls($users, $projects);

        $this->command->info('CommunicationSeeder: notifications, and video calls seeded.');
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
                DB::table('notifications')->insert([
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

            DB::table('video_calls')->insert([
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
                DB::table('call_participants')->insertOrIgnore([
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
            DB::table('video_calls')->insert([
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
