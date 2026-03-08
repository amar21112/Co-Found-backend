<?php

namespace Database\Seeders;

use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Database\Seeder;

class PasswordResetSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('email_verified', true)->get();

        foreach ($users->random(min(20, $users->count())) as $user) {
            // Create valid reset tokens
            if (rand(0, 1)) {
                PasswordReset::factory()
                    ->valid()
                    ->create(['user_id' => $user->id]);
            }

            // Create expired tokens
            if (rand(0, 2) === 0) {
                PasswordReset::factory()
                    ->expired()
                    ->create(['user_id' => $user->id]);
            }

            // Create used tokens
            if (rand(0, 3) === 0) {
                PasswordReset::factory()
                    ->used()
                    ->create(['user_id' => $user->id]);
            }
        }
    }
}
