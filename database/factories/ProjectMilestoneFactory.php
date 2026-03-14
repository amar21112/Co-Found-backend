<?php

namespace Database\Factories;

use App\Models\ProjectMilestone;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectMilestoneFactory extends Factory
{
    protected $model = ProjectMilestone::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'in_progress', 'completed', 'overdue']);

        return [
            'id'             => $this->faker->uuid(),
            'project_id'     => Project::factory(),
            'title'          => $this->faker->bs(),
            'description'    => $this->faker->paragraph(),
            'due_date'       => $this->faker->dateTimeBetween('+1 week', '+6 months')->format('Y-m-d'),
            'completed_date' => $status === 'completed' ? $this->faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d') : null,
            'status'         => $status,
            'order_index'    => $this->faker->numberBetween(1, 20),
        ];
    }
}
