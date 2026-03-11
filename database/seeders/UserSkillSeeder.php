<?php

namespace Database\Seeders;

use App\Models\UserSkill;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSkillSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Skip if user already has skills
            if ($user->skills()->count() > 0) {
                continue;
            }

            $skillCount = rand(2, 8);

            UserSkill::factory([
                'user_id' => $user->id,
            ])
                ->count($skillCount)
                ->approved()
                ->create();
        }
    }
}
