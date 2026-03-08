<?php

namespace Database\Seeders;

use App\Models\VerificationAttempt;
use App\Models\User;
use Illuminate\Database\Seeder;

class VerificationAttemptSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('identity_verified', true)->get();

        foreach ($users as $user) {
            // Create successful attempts
            VerificationAttempt::factory()
                ->success()
                ->create([
                    'user_id' => $user->id,
                    'attempt_number' => 1
                ]);

            // Create failed attempts for some users
            if (rand(0, 1)) {
                VerificationAttempt::factory()
                    ->count(rand(1, 3))
                    ->failure()
                    ->create([
                        'user_id' => $user->id,
                        'attempt_number' => function () {
                            static $attempt = 2;
                            return $attempt++;
                        }
                    ]);
            }
        }

        // Create attempts for non-verified users
        $pendingUsers = User::where('identity_verified', false)->get();
        foreach ($pendingUsers->random(min(20, $pendingUsers->count())) as $user) {
            VerificationAttempt::factory()
                ->count(rand(1, 5))
                ->failure()
                ->create(['user_id' => $user->id]);
        }
    }
}
