<?php

namespace Database\Seeders;

use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Seeder;

class SessionSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('account_status', 'active')->get();

        foreach ($users as $user) {
            // Create active sessions for online users
            if (rand(0, 2) === 0) {
                Session::factory()
                    ->count(rand(1, 2))
                    ->create(['user_id' => $user->id]);
            }

            // Create expired sessions
            Session::factory()
                ->count(rand(1, 5))
                ->expired()
                ->create(['user_id' => $user->id]);
        }
    }
}
