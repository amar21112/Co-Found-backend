<?php

namespace Database\Factories;

use App\Models\UserSkill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserSkillFactory extends Factory
{
    protected $model = UserSkill::class;

    public function definition(): array
    {
        $skills = [
            'PHP', 'Laravel', 'JavaScript', 'React', 'Vue.js', 'Python', 'Django',
            'UI/UX Design', 'Figma', 'Adobe XD', 'Project Management', 'Agile',
            'Digital Marketing', 'SEO', 'Content Writing', 'Data Analysis',
            'Machine Learning', 'DevOps', 'AWS', 'Docker', 'Kubernetes',
            'Node.js', 'TypeScript', 'GraphQL', 'PostgreSQL', 'MongoDB',
            'Redis', 'Go', 'Rust', 'Swift', 'Kotlin', 'Flutter', 'React Native'
        ];

        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'skill_name' => $this->faker->randomElement($skills),
            'proficiency_level' => $this->faker->numberBetween(1, 5),
            'years_experience' => $this->faker->randomFloat(1, 0.5, 15),
            'is_approved' => $this->faker->boolean(80),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
        ]);
    }

    public function expert(): static
    {
        return $this->state(fn (array $attributes) => [
            'proficiency_level' => 5,
            'years_experience' => $this->faker->numberBetween(5, 15),
        ]);
    }

    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'proficiency_level' => 1,
            'years_experience' => $this->faker->randomFloat(1, 0, 1),
        ]);
    }
}
