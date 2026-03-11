<?php

namespace Database\Factories;

use App\Models\ProjectSkill;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectSkillFactory extends Factory
{
    protected $model = ProjectSkill::class;

    public function definition(): array
    {
        $skills = [
            'React', 'Node.js', 'Python', 'TensorFlow', 'PostgreSQL',
            'TypeScript', 'GraphQL', 'MongoDB', 'Docker', 'AWS',
            'Vue.js', 'Angular', 'Laravel', 'Django', 'Flask',
            'Redis', 'Elasticsearch', 'Kubernetes', 'Terraform',
            'Swift', 'Kotlin', 'Flutter', 'React Native', 'PHP',
            'Java', 'Spring Boot', 'C#', '.NET', 'Go', 'Rust'
        ];

        return [
            'id' => Str::uuid(),
            'project_id' => Project::factory(),
            'skill_name' => $this->faker->randomElement($skills),
            'proficiency_required' => $this->faker->numberBetween(3, 5),
            'positions_needed' => $this->faker->numberBetween(1, 3),
            'positions_filled' => $this->faker->numberBetween(0, 2),
            'is_required' => $this->faker->boolean(80),
        ];
    }

    public function required(): static
    {
        return $this->state([
            'is_required' => true,
        ]);
    }

    public function filled(): static
    {
        return $this->state([
            'positions_filled' => $attributes['positions_needed'],
        ]);
    }
}
