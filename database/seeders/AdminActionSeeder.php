<?php

namespace Database\Seeders;

use App\Models\AdminAction;
use App\Models\User;
use App\Models\Project;
use App\Models\Report;
use Illuminate\Database\Seeder;

class AdminActionSeeder extends Seeder
{
    public function run(): void
    {
        $admins = User::where('role', 'administrator')->get();

        for ($i = 0; $i < 100; $i++) {
            $admin = $admins->random();
            $actionType = $this->getRandomActionType();

            $factory = AdminAction::factory();

            if (str_contains($actionType, 'user')) {
                $factory->userAction();
            } elseif (str_contains($actionType, 'content')) {
                $factory->contentAction();
            } elseif (str_contains($actionType, 'report')) {
                $factory->reportAction();
            }

            $factory->create([
                'admin_id' => $admin->id,
                'action_type' => $actionType,
                'ip_address' => fake()->ipv4()
            ]);
        }
    }

    private function getRandomActionType()
    {
        $types = [
            'user_suspended' => 20,
            'user_banned' => 10,
            'user_verified' => 15,
            'content_removed' => 25,
            'project_featured' => 10,
            'settings_changed' => 10,
            'report_resolved' => 10
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($types as $type => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $type;
            }
        }

        return 'content_removed';
    }
}
