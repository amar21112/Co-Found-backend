<?php

namespace Database\Factories;

use App\Models\ProjectRole;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectRoleFactory extends Factory
{
    protected $model = ProjectRole::class;

    public function definition(): array
    {
        $roles = [
            'Frontend Developer', 'Backend Developer', 'Full Stack Developer',
            'ML Engineer', 'DevOps Engineer', 'UI/UX Designer',
            'Project Manager', 'Product Owner', 'QA Tester',
            'Data Scientist', 'Mobile Developer', 'Security Engineer',
            'Technical Writer', 'Marketing Specialist', 'Business Analyst',
            'Scrum Master', 'Architect', 'Database Administrator'
        ];

        return [
            'id' => Str::uuid(),
            'project_id' => Project::factory(),
            'role_name' => $this->faker->randomElement($roles),
            'description' => $this->faker->optional(0.7)->sentence(),
            'positions_needed' => $this->faker->numberBetween(1, 3),
            'positions_filled' => $this->faker->numberBetween(0, 2),
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function filled(): static
    {
        return $this->state(fn (array $attributes) => [
            'positions_filled' => $attributes['positions_needed'],
        ]);
    }
}
