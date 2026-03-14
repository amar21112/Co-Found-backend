<?php

namespace Database\Factories;

use App\Models\CollaborationRating;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollaborationRatingFactory extends Factory
{
    protected $model = CollaborationRating::class;

    public function definition(): array
    {
        $ratings = [
            $this->faker->numberBetween(1, 5),
            $this->faker->numberBetween(1, 5),
            $this->faker->numberBetween(1, 5),
            $this->faker->numberBetween(1, 5),
            $this->faker->numberBetween(1, 5),
        ];

        return [
            'id'                      => $this->faker->uuid(),
            'rater_id'                => User::factory(),
            'rated_user_id'           => User::factory(),
            'project_id'              => Project::factory(),
            'communication_rating'    => $ratings[0],
            'reliability_rating'      => $ratings[1],
            'skill_rating'            => $ratings[2],
            'problem_solving_rating'  => $ratings[3],
            'teamwork_rating'         => $ratings[4],
            'overall_rating'          => round(array_sum($ratings) / 5, 2),
            'written_feedback'        => $this->faker->paragraph(),
            'visibility'              => $this->faker->randomElement(['public', 'private', 'anonymous']),
        ];
    }
}
