<?php

namespace Database\Factories;

use App\Models\ProjectApplication;
use App\Models\Project;
use App\Models\User;
use App\Models\ProjectRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectApplicationFactory extends Factory
{
    protected $model = ProjectApplication::class;

    public function definition(): array
    {
        $statuses = ['pending', 'reviewing', 'accepted', 'rejected', 'withdrawn', 'expired'];

        return [
            'id' => Str::uuid(),
            'project_id' => Project::factory(),
            'applicant_id' => User::factory(),
            'role_id' => $this->faker->optional(0.7)->randomElement([ProjectRole::factory()]),
            'cover_message' => $this->faker->paragraphs(3, true),
            'proposed_role' => $this->faker->jobTitle(),
            'availability' => $this->faker->randomElement(['full-time', 'part-time', 'weekends', 'evenings']),
            'status' => $this->faker->randomElement($statuses),
            'match_score' => $this->faker->optional(0.8)->randomFloat(2, 0.5, 1.0),
            'reviewed_by' => $this->faker->optional(0.3)->randomElement([User::factory()]),
            'reviewed_at' => function (array $attributes) {
                return $attributes['reviewed_by'] ? $this->faker->dateTimeBetween('-1 month', 'now') : null;
            },
            'applied_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'created_at' => function (array $attributes) {
                return $attributes['applied_at'];
            },
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['applied_at'], 'now');
            },
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => 'accepted',
            'reviewed_at' => $this->faker->dateTimeBetween('-2 weeks', 'now'),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => 'rejected',
            'reviewed_at' => $this->faker->dateTimeBetween('-2 weeks', 'now'),
        ]);
    }

    public function highMatch(): static
    {
        return $this->state([
            'match_score' => $this->faker->randomFloat(2, 0.85, 1.0),
        ]);
    }
}
