<?php

namespace Database\Factories;

use App\Models\MatchModel;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MatchFactory extends Factory
{
    protected $model = MatchModel::class;

    public function definition(): array
    {
        $types = ['collaborator', 'project'];

        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'matched_user_id' => $this->faker->optional(0.6)->randomElement([User::factory()]),
            'matched_project_id' => $this->faker->optional(0.4)->randomElement([Project::factory()]),
            'match_type' => function (array $attributes) {
                return $attributes['matched_project_id'] ? 'project' : 'collaborator';
            },
            'compatibility_score' => $this->faker->randomFloat(2, 0.5, 0.99),
            'match_reasons' => [
                $this->faker->randomElement(['Skill match', 'Availability match', 'Past collaboration success', 'Similar interests']),
                $this->faker->randomElement(['Location match', 'Experience level match', 'Mutual connections']),
            ],
            'viewed' => $this->faker->boolean(60),
            'viewed_at' => function (array $attributes) {
                return $attributes['viewed'] ? $this->faker->dateTimeBetween('-2 weeks', 'now') : null;
            },
            'saved' => $this->faker->boolean(20),
            'action_taken' => $this->faker->boolean(15),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('+1 week', '+2 months'),
        ];
    }

    public function collaborator(): static
    {
        return $this->state([
            'match_type' => 'collaborator',
            'matched_user_id' => User::factory(),
            'matched_project_id' => null,
        ]);
    }

    public function project(): static
    {
        return $this->state([
            'match_type' => 'project',
            'matched_user_id' => null,
            'matched_project_id' => Project::factory(),
        ]);
    }

    public function highScore(): static
    {
        return $this->state([
            'compatibility_score' => $this->faker->randomFloat(2, 0.85, 0.99),
        ]);
    }

    public function unviewed(): static
    {
        return $this->state([
            'viewed' => false,
            'viewed_at' => null,
        ]);
    }

    public function saved(): static
    {
        return $this->state([
            'saved' => true,
        ]);
    }
}
