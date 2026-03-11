<?php

namespace Database\Factories;

use App\Models\ProjectMilestone;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectMilestoneFactory extends Factory
{
    protected $model = ProjectMilestone::class;

    public function definition(): array
    {
        $statuses = ['pending', 'in_progress', 'completed', 'delayed'];
        $titles = [
            'Project Kickoff', 'Requirements Gathering', 'Design Phase',
            'MVP Development', 'Alpha Testing', 'Beta Release',
            'User Testing', 'Bug Fixes', 'Performance Optimization',
            'Documentation', 'Launch Preparation', 'Public Release',
            'Post-Launch Review', 'Maintenance Phase'
        ];

        $dueDate = $this->faker->dateTimeBetween('-1 month', '+3 months');
        $completed = $this->faker->boolean(30) && $dueDate < now();

        return [
            'id' => Str::uuid(),
            'project_id' => Project::factory(),
            'title' => $this->faker->randomElement($titles),
            'description' => $this->faker->optional(0.6)->paragraph(),
            'due_date' => $dueDate,
            'completed_date' => $completed ? $this->faker->dateTimeBetween('-2 months', 'now') : null,
            'status' => $completed ? 'completed' : $this->faker->randomElement(['pending', 'in_progress']),
            'order_index' => $this->faker->numberBetween(1, 10),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'completed_date' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => 'in_progress',
            'completed_date' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'completed_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function delayed(): static
    {
        return $this->state([
            'status' => 'delayed',
            'due_date' => $this->faker->dateTimeBetween('-2 weeks', '-1 day'),
        ]);
    }
}
