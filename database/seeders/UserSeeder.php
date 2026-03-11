<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\NotificationPreference;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create just one admin user to test
        User::factory()
            ->admin()
            ->active()
            ->verified()
            ->create([
                'email' => 'admin@cofound.com',
                'username' => 'admin',
                'full_name' => 'Admin User',
            ]);

        // Create one regular user
        User::factory()
            ->regularUser()
            ->active()
            ->create([
                'email' => 'john.doe@example.com',
                'username' => 'johndoe',
                'full_name' => 'John Doe',
            ]);

        // Create notification preferences for these users
        foreach (User::all() as $user) {
            NotificationPreference::factory()->create([
                'user_id' => $user->id
            ]);
        }
    }
}
