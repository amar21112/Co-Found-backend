<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['direct', 'group', 'project']);

        return [
            'id'                => $this->faker->uuid(),
            'conversation_type' => $type,
            'title'             => $type !== 'direct' ? $this->faker->words(3, true) : null,
            'project_id'        => $type === 'project' ? Project::factory() : null,
            'created_by'        => User::factory(),
            'last_message_at'   => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }

    public function direct(): static
    {
        return $this->state(fn() => ['conversation_type' => 'direct', 'title' => null, 'project_id' => null]);
    }

    public function project(): static
    {
        return $this->state(fn() => [
            'conversation_type' => 'project',
            'title'             => $this->faker->words(3, true),
            'project_id'        => Project::factory(),
        ]);
    }
}
