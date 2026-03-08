<?php

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnalyticsEventSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('account_status', 'active')->get();

        for ($i = 0; $i < 1000; $i++) {
            $eventType = $this->getRandomEventType();
            $user = rand(0, 3) ? $users->random() : null; // 75% authenticated, 25% anonymous

            $factory = AnalyticsEvent::factory();

            if ($eventType === 'page_view') {
                $factory->pageView();
            } elseif ($eventType === 'project_view') {
                $factory->projectView();
            } elseif ($eventType === 'search') {
                $factory->search();
            }

            if ($user) {
                $factory->authenticated();
            } else {
                $factory->anonymous();
            }

            $factory->create([
                'event_type' => $eventType,
                'user_id' => $user?->id,
                'created_at' => now()->subHours(rand(0, 720))->subMinutes(rand(0, 59))
            ]);
        }
    }

    private function getRandomEventType()
    {
        $events = [
            'page_view' => 40,
            'project_view' => 15,
            'profile_view' => 10,
            'search' => 8,
            'application_submitted' => 5,
            'message_sent' => 7,
            'connection_request' => 5,
            'login' => 5,
            'registration' => 3,
            'project_created' => 2
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($events as $event => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $event;
            }
        }

        return 'page_view';
    }
}
