<?php

namespace Database\Seeders;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationPreferenceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Skip if already has preferences
            if ($user->notificationPreference) {
                continue;
            }

            $factory = NotificationPreference::factory();

            if (rand(0, 3) === 0) {
                $factory->allEnabled();
            }

            if (rand(0, 2) === 0) {
                $factory->quietHours();
            }

            if (rand(0, 2) === 0) {
                $factory->digestDaily();
            }

            $factory->create(['user_id' => $user->id]);
        }
    }
}
