<?php

namespace Database\Factories;

use App\Models\PortfolioSkill;
use App\Models\PortfolioItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PortfolioSkillFactory extends Factory
{
    protected $model = PortfolioSkill::class;

    public function definition(): array
    {
        $skills = [
            'PHP', 'Laravel', 'JavaScript', 'React', 'Vue.js', 'Python',
            'UI/UX Design', 'Figma', 'Project Management', 'Data Analysis',
            'Machine Learning', 'DevOps', 'AWS', 'Docker', 'Node.js',
            'TypeScript', 'GraphQL', 'PostgreSQL', 'MongoDB', 'Flutter'
        ];

        return [
            'id' => Str::uuid(),
            'portfolio_item_id' => PortfolioItem::factory(),
            'skill_name' => $this->faker->randomElement($skills),
        ];
    }
}
