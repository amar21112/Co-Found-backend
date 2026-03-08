<?php

namespace Database\Seeders;

use App\Models\UserConnection;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserConnectionSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('account_status', 'active')->get();

        foreach ($users as $user) {
            $connectionCount = rand(5, 30);
            $potentialConnections = $users->where('id', '!=', $user->id)
                ->random(min($connectionCount, $users->count() - 1));

            foreach ($potentialConnections as $connection) {
                // Check if connection already exists
                $exists = UserConnection::where(function ($query) use ($user, $connection) {
                    $query->where('requester_id', $user->id)
                        ->where('recipient_id', $connection->id);
                })->orWhere(function ($query) use ($user, $connection) {
                    $query->where('requester_id', $connection->id)
                        ->where('recipient_id', $user->id);
                })->exists();

                if (!$exists) {
                    $status = $this->getRandomStatus();

                    $factory = UserConnection::factory();

                    if ($status === 'accepted') {
                        $factory->accepted();
                    } elseif ($status === 'pending') {
                        $factory->pending();
                    } elseif ($status === 'blocked') {
                        $factory->blocked();
                    }

                    $factory->create([
                        'requester_id' => $status === 'pending' ? $user->id : ($status === 'accepted' ? $user->id : $connection->id),
                        'recipient_id' => $status === 'pending' ? $connection->id : ($status === 'accepted' ? $connection->id : $user->id)
                    ]);
                }
            }
        }
    }

    private function getRandomStatus()
    {
        $statuses = [
            'pending' => 30,
            'accepted' => 60,
            'rejected' => 8,
            'blocked' => 2
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $status;
            }
        }

        return 'accepted';
    }
}
