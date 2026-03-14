<?php

namespace Database\Factories;

use App\Models\ProjectTeamMember;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectTeamMemberFactory extends Factory
{
    protected $model = ProjectTeamMember::class;

    public function definition(): array
    {
        return [
            'id'          => $this->faker->uuid(),
            'project_id'  => Project::factory(),
            'user_id'     => User::factory(),
            'role_id'     => null,
            'position'    => $this->faker->randomElement(['Lead Developer', 'Designer', 'Backend Engineer', 'Frontend Developer', 'DevOps', 'Product Manager']),
            'permissions' => $this->faker->randomElement(['member', 'admin', 'owner']),
            'joined_at'   => $this->faker->dateTimeBetween('-6 months', 'now'),
            'left_at'     => null,
            'is_active'   => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn() => [
            'is_active' => false,
            'left_at'   => $this->faker->dateTimeBetween('-3 months', 'now'),
        ]);
    }
}
