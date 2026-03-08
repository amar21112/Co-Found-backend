<?php

namespace Database\Factories;

use App\Models\ProjectTeamMember;
use App\Models\Project;
use App\Models\User;
use App\Models\ProjectRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectTeamMemberFactory extends Factory
{
    protected $model = ProjectTeamMember::class;

    public function definition(): array
    {
        $permissions = ['member', 'co-owner', 'owner', 'viewer'];
        $positions = ['Developer', 'Designer', 'Project Manager', 'QA Engineer', 'DevOps Engineer'];

        return [
            'id' => Str::uuid(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'role_id' => $this->faker->optional(0.7)->randomElement([ProjectRole::factory()]),
            'position' => $this->faker->randomElement($positions),
            'permissions' => $this->faker->randomElement($permissions),
            'joined_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'left_at' => $this->faker->optional(0.1)->dateTimeBetween('-1 month', 'now'),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => 'owner',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'left_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'left_at' => $this->faker->dateTimeBetween('-3 months', '-1 day'),
        ]);
    }
}
