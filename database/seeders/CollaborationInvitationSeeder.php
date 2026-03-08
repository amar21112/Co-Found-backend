<?php

namespace Database\Seeders;

use App\Models\CollaborationInvitation;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Seeder;

class CollaborationInvitationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('account_status', 'active')->get();
        $projects = Project::where('status', 'active')->get();

        foreach ($users->random(min(50, $users->count())) as $sender) {
            $invitationCount = rand(1, 5);
            $recipients = $users->where('id', '!=', $sender->id)
                ->random(min($invitationCount, $users->count() - 1));

            foreach ($recipients as $recipient) {
                $type = $this->getRandomType();
                $status = $this->getRandomStatus();

                $factory = CollaborationInvitation::factory();

                if ($status === 'pending') {
                    $factory->pending();
                } elseif ($status === 'accepted') {
                    $factory->accepted();
                } elseif ($status === 'declined') {
                    $factory->declined();
                } elseif ($status === 'expired') {
                    $factory->expired();
                }

                $factory->create([
                    'sender_id' => $sender->id,
                    'recipient_id' => $recipient->id,
                    'project_id' => $type === 'project_join' && $projects->count() > 0 ? $projects->random()->id : null,
                    'invitation_type' => $type
                ]);
            }
        }
    }

    private function getRandomType()
    {
        $types = ['project_join', 'team_invite', 'collaboration_request', 'mentorship'];
        return $types[array_rand($types)];
    }

    private function getRandomStatus()
    {
        $statuses = [
            'pending' => 50,
            'accepted' => 25,
            'declined' => 15,
            'expired' => 7,
            'withdrawn' => 3
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $status;
            }
        }

        return 'pending';
    }
}
