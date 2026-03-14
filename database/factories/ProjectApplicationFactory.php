<?php

namespace Database\Factories;

use App\Models\ProjectApplication;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectApplicationFactory extends Factory
{
    protected $model = ProjectApplication::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'reviewing', 'accepted', 'rejected', 'withdrawn']);

        return [
            'id'            => $this->faker->uuid(),
            'project_id'    => Project::factory(),
            'applicant_id'  => User::factory(),
            'role_id'       => null,
            'cover_message' => $this->faker->paragraphs(2, true),
            'proposed_role' => $this->faker->randomElement(['Developer', 'Designer', 'Product Manager', 'Marketing Lead', 'DevOps Engineer']),
            'availability'  => $this->faker->randomElement(['full_time', 'part_time', 'weekends', 'flexible']),
            'status'        => $status,
            'match_score'   => $this->faker->randomFloat(2, 0.30, 1.00),
            'reviewed_by'   => in_array($status, ['accepted', 'rejected']) ? User::factory() : null,
            'reviewed_at'   => in_array($status, ['accepted', 'rejected']) ? $this->faker->dateTimeBetween('-14 days', 'now') : null,
            'applied_at'    => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn() => ['status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null]);
    }

    public function accepted(): static
    {
        return $this->state(fn() => [
            'status'      => 'accepted',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now()->subDays(rand(1, 7)),
        ]);
    }
}
