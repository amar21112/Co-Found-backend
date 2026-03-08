<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $statuses = ['planning', 'active', 'on_hold', 'completed', 'cancelled'];
        $visibilities = ['public', 'private', 'unlisted'];
        $categories = [
            'Web Development', 'Mobile App', 'AI/ML', 'Design',
            'Marketing', 'Research', 'Open Source', 'Game Development',
            'Blockchain', 'IoT', 'DevOps', 'Data Science'
        ];

        $title = $this->faker->unique()->words(3, true);
        $startDate = $this->faker->optional(0.7)->dateTimeBetween('-3 months', '+1 month');
        $targetDate = $startDate ? $this->faker->dateTimeBetween($startDate, '+6 months') : null;

        return [
            'id' => Str::uuid(),
            'owner_id' => User::factory(),
            'title' => ucfirst($title),
            'slug' => Str::slug($title) . '-' . $this->faker->unique()->numberBetween(100, 999),
            'short_description' => $this->faker->sentence(12),
            'full_description' => $this->faker->paragraphs(6, true),
            'category' => $this->faker->randomElement($categories),
            'status' => $this->faker->randomElement($statuses),
            'visibility' => $this->faker->randomElement($visibilities),
            'team_size_min' => $this->faker->optional(0.8)->numberBetween(1, 3),
            'team_size_max' => $this->faker->optional(0.8)->numberBetween(4, 12),
            'current_team_size' => $this->faker->numberBetween(1, 8),
            'start_date' => $startDate,
            'target_completion_date' => $targetDate,
            'actual_completion_date' => function (array $attributes) {
                return $attributes['status'] === 'completed'
                    ? $this->faker->dateTimeBetween($attributes['start_date'] ?? '-2 months', 'now')
                    : null;
            },
            'is_accepting_applications' => $this->faker->boolean(70),
            'application_deadline' => $this->faker->optional(0.3)->dateTimeBetween('now', '+3 months'),
            'view_count' => $this->faker->numberBetween(0, 1000),
            'application_count' => $this->faker->numberBetween(0, 30),
            'published_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
            'archived_at' => function (array $attributes) {
                return $attributes['status'] === 'completed'
                    ? $this->faker->optional(0.7)->dateTimeBetween($attributes['updated_at'], '+1 year')
                    : null;
            },
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    public function planning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planning',
            'is_accepting_applications' => true,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'is_accepting_applications' => true,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'actual_completion_date' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'is_accepting_applications' => false,
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'private',
            'is_accepting_applications' => false,
        ]);
    }

    public function acceptingApplications(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_accepting_applications' => true,
        ]);
    }
}
