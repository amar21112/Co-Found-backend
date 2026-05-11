<?php

namespace Database\Factories;

use App\Enums\MatchType;
use App\Models\MatchModel;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatchFactory extends Factory
{
    protected $model = MatchModel::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(MatchType::cases());

        return [
            'user_id'             => User::factory(),
            'matched_user_id'     => $type === MatchType::Collaborator ? User::factory() : null,
            'matched_project_id'  => $type === MatchType::Project ? Project::factory() : null,
            'match_type'          => $type->value,
            'compatibility_score' => $this->faker->randomFloat(2, 0.50, 1.00),
            'match_reasons'       => [
                'skill_overlap'    => $this->faker->randomFloat(2, 0.5, 1.0),
                'location_match'   => $this->faker->boolean(60),
                'goals_alignment'  => $this->faker->randomFloat(2, 0.4, 1.0),
                'experience_match' => $this->faker->randomFloat(2, 0.3, 1.0),
            ],
            'viewed'      => false,
            'viewed_at'   => null,
            'saved'       => false,
            'action_taken'=> false,
            'expires_at'  => now()->addDays(30),
            'created_at'  => now(),
        ];
    }

    public function collaborator(): static
    {
        return $this->state(fn() => [
            'match_type'         => MatchType::Collaborator->value,
            'matched_user_id'    => User::factory(),
            'matched_project_id' => null,
        ]);
    }

    public function project(): static
    {
        return $this->state(fn() => [
            'match_type'          => MatchType::Project->value,
            'matched_user_id'     => null,
            'matched_project_id'  => Project::factory(),
        ]);
    }

    public function viewed(): static
    {
        return $this->state(fn() => [
            'viewed'    => true,
            'viewed_at' => now()->subHours(rand(1, 48)),
        ]);
    }

    public function saved(): static
    {
        return $this->state(fn() => ['saved' => true]);
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function highScore(): static
    {
        return $this->state(fn() => [
            'compatibility_score' => $this->faker->randomFloat(2, 0.85, 1.00),
        ]);
    }
}
