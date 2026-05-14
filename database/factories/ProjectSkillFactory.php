<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectSkill;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectSkillFactory extends Factory
{
    protected $model = ProjectSkill::class;

    public function definition(): array
    {
        return [
            'id'                   => $this->faker->uuid(),
            'project_id'           => Project::factory(),
            'skill_name'           => $this->faker->unique()->word(),
            'proficiency_required' => $this->faker->numberBetween(1, 5),
            'positions_needed'     => 1,
            'positions_filled'     => 0,
            'is_required'          => true,
        ];
    }
}
