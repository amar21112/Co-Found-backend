<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('account_status', 'active')->get();

        foreach ($users as $user) {
            $notificationCount = rand(5, 20);

            for ($i = 0; $i < $notificationCount; $i++) {
                $read = rand(0, 1);
                $createdAt = now()->subDays(rand(0, 30))->subHours(rand(0, 23));

                $factory = Notification::factory();

                if ($read) {
                    $factory->read();
                } else {
                    $factory->unread();
                }

                if (rand(0, 4) === 0) {
                    $factory->highPriority();
                }

                $factory->create([
                    'user_id' => $user->id,
                    'created_at' => $createdAt
                ]);
            }
        }
    }
}
