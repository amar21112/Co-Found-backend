<?php

namespace Database\Factories;

use App\Models\UserSkill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSkillFactory extends Factory
{
    protected $model = UserSkill::class;

    protected static array $skills = [
        'PHP', 'Laravel', 'JavaScript', 'TypeScript', 'React', 'Vue.js', 'Node.js',
        'Python', 'Django', 'FastAPI', 'Java', 'Spring Boot', 'Go', 'Rust',
        'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Docker', 'Kubernetes',
        'AWS', 'Azure', 'GCP', 'UI/UX Design', 'Figma', 'Product Management',
        'Data Science', 'Machine Learning', 'DevOps', 'Blockchain', 'Swift', 'Kotlin',
    ];

    public function definition(): array
    {
        return [
            'id'                => $this->faker->uuid(),
            'user_id'           => User::factory(),
            'skill_name'        => $this->faker->randomElement(self::$skills),
            'proficiency_level' => $this->faker->numberBetween(1, 5),
            'years_experience'  => $this->faker->randomFloat(1, 0.5, 15),
            'is_approved'       => $this->faker->boolean(70),
        ];
    }
}
