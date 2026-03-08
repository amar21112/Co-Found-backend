<?php

namespace Database\Factories;

use App\Models\ApplicationSkill;
use App\Models\ProjectApplication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ApplicationSkillFactory extends Factory
{
    protected $model = ApplicationSkill::class;

    public function definition(): array
    {
        $skills = [
            'React', 'Node.js', 'Python', 'JavaScript', 'TypeScript',
            'PHP', 'Laravel', 'Vue.js', 'Angular', 'Django',
            'PostgreSQL', 'MongoDB', 'MySQL', 'GraphQL', 'REST API',
            'Docker', 'AWS', 'Azure', 'GCP', 'Kubernetes'
        ];

        return [
            'id' => Str::uuid(),
            'application_id' => ProjectApplication::factory(),
            'skill_name' => $this->faker->randomElement($skills),
            'proficiency_claimed' => $this->faker->numberBetween(3, 5),
        ];
    }
}
