<?php

namespace Database\Factories;

use App\Models\CollaborationRating;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CollaborationRatingFactory extends Factory
{
    protected $model = CollaborationRating::class;

    public function definition(): array
    {
        $visibilities = ['public', 'private', 'anonymous'];

        $communication = $this->faker->numberBetween(3, 5);
        $reliability = $this->faker->numberBetween(3, 5);
        $skill = $this->faker->numberBetween(3, 5);
        $problemSolving = $this->faker->numberBetween(3, 5);
        $teamwork = $this->faker->numberBetween(3, 5);

        $overall = ($communication + $reliability + $skill + $problemSolving + $teamwork) / 5;

        return [
            'id' => Str::uuid(),
            'rater_id' => User::factory(),
            'rated_user_id' => User::factory(),
            'project_id' => $this->faker->optional(0.7)->randomElement([Project::factory()]),
            'communication_rating' => $communication,
            'reliability_rating' => $reliability,
            'skill_rating' => $skill,
            'problem_solving_rating' => $problemSolving,
            'teamwork_rating' => $teamwork,
            'overall_rating' => $overall,
            'written_feedback' => $this->faker->optional(0.6)->paragraph(),
            'visibility' => $this->faker->randomElement($visibilities),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function public(): static
    {
        return $this->state([
            'visibility' => 'public',
        ]);
    }

    public function anonymous(): static
    {
        return $this->state([
            'visibility' => 'anonymous',
        ]);
    }

    public function highRating(): static
    {
        $communication = 5;
        $reliability = 5;
        $skill = 5;
        $problemSolving = 5;
        $teamwork = 5;

        return $this->state([
            'communication_rating' => $communication,
            'reliability_rating' => $reliability,
            'skill_rating' => $skill,
            'problem_solving_rating' => $problemSolving,
            'teamwork_rating' => $teamwork,
            'overall_rating' => 5.0,
        ]);
    }

    public function withFeedback(): static
    {
        return $this->state([
            'written_feedback' => $this->faker->paragraph(),
        ]);
    }
}
