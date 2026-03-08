<?php

namespace Database\Seeders;

use App\Models\PortfolioItem;
use App\Models\PortfolioSkill;
use App\Models\User;
use Illuminate\Database\Seeder;

class PortfolioItemSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', '!=', 'guest')->get();

        foreach ($users as $user) {
            // Skip if user already has portfolio items
            if ($user->portfolioItems()->count() > 0) {
                continue;
            }

            $itemCount = rand(1, 5);

            PortfolioItem::factory()
                ->count($itemCount)
                ->forUser($user->id)
                ->public()
                ->create()
                ->each(function ($item) {
                    PortfolioSkill::factory()
                        ->count(rand(1, 4))
                        ->forPortfolioItem($item->id)
                        ->create();
                });
        }
    }
}
