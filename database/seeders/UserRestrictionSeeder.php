<?php

namespace Database\Seeders;

use App\Models\UserRestriction;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserRestrictionSeeder extends Seeder
{
    public function run(): void
    {
        $admins = User::where('role', 'administrator')->get();
        $moderators = User::where('role', 'moderator')->get();
        $restrictors = $admins->concat($moderators);

        $users = User::whereIn('account_status', ['suspended', 'banned'])->get();

        foreach ($users as $user) {
            $type = $user->account_status === 'banned' ? 'ban' : 'suspension';

            $factory = UserRestriction::factory();

            if ($type === 'suspension') {
                $factory->suspension();
            } elseif ($type === 'ban') {
                $factory->ban();
            }

            $factory->active()->create([
                'user_id' => $user->id,
                'restricted_by' => $restrictors->random()->id
            ]);
        }

        // Create warning restrictions
        for ($i = 0; $i < 20; $i++) {
            $user = User::where('account_status', 'active')->inRandomOrder()->first();

            UserRestriction::factory()
                ->warning()
                ->active()
                ->create([
                    'user_id' => $user->id,
                    'restricted_by' => $restrictors->random()->id
                ]);
        }
    }
}
