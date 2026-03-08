<?php

namespace Database\Seeders;

use App\Models\PortfolioSkill;
use App\Models\PortfolioItem;
use Illuminate\Database\Seeder;

class PortfolioSkillSeeder extends Seeder
{
    public function run(): void
    {
        $items = PortfolioItem::all();

        foreach ($items as $item) {
            // Skip if already has skills
            if ($item->skills()->count() > 0) {
                continue;
            }

            PortfolioSkill::factory()
                ->count(rand(1, 4))
                ->forPortfolioItem($item->id)
                ->create();
        }
    }
}
