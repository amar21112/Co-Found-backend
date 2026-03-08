<?php

namespace Database\Factories;

use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MatchFeedbackFactory extends Factory
{
    protected $model = MatchFeedback::class;

    public function definition(): array
    {
        $types = ['relevant', 'not_relevant', 'already_connected', 'not_interested'];

        return [
            'id' => Str::uuid(),
            'match_id' => MatchModel::factory(),
            'user_id' => User::factory(),
            'feedback_type' => $this->faker->randomElement($types),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function relevant(): static
    {
        return $this->state(fn (array $attributes) => [
            'feedback_type' => 'relevant',
        ]);
    }

    public function notRelevant(): static
    {
        return $this->state(fn (array $attributes) => [
            'feedback_type' => 'not_relevant',
        ]);
    }
}
