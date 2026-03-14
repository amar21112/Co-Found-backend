<?php

namespace Database\Factories;

use App\Models\MatchModel;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatchFactory extends Factory
{
    protected $model = MatchModel::class;

    public function definition(): array
    {
        $matchType = $this->faker->randomElement(['user_to_user', 'user_to_project']);
        $viewed    = $this->faker->boolean(60);

        return [
            'id'                  => $this->faker->uuid(),
            'user_id'             => User::factory(),
            'matched_user_id'     => $matchType === 'user_to_user' ? User::factory() : null,
            'matched_project_id'  => $matchType === 'user_to_project' ? Project::factory() : null,
            'match_type'          => $matchType,
            'compatibility_score' => $this->faker->randomFloat(2, 0.50, 1.00),
            'match_reasons'       => json_encode([
                'skills_overlap'    => $this->faker->randomFloat(2, 0, 1),
                'location_match'    => $this->faker->boolean(),
                'availability_fit'  => $this->faker->boolean(),
            ]),
            'viewed'              => $viewed,
            'viewed_at'           => $viewed ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
            'saved'               => $this->faker->boolean(20),
            'action_taken'        => $this->faker->boolean(30),
            'expires_at'          => $this->faker->dateTimeBetween('+7 days', '+30 days'),
        ];
    }
}
