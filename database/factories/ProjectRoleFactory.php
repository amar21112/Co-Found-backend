<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectRole>
 */
class ProjectRoleFactory extends Factory
{
    protected $model = ProjectRole::class;

    public function definition(): array
    {
        return [
            'id'               => $this->faker->uuid(),
            'project_id'       => Project::factory(),
            'role_name'        => $this->faker->unique()->jobTitle(),
            'description'      => $this->faker->sentence(),
            'positions_needed' => $this->faker->numberBetween(1, 5),
            'positions_filled' => 0,
        ];
    }

    public function filled(): static
    {
        return $this->state(fn(array $attrs) => [
            'positions_filled' => $attrs['positions_needed'] ?? 1,
        ]);
    }
}
