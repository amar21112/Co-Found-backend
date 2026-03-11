<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        $types = ['direct', 'group'];

        return [
            'id' => Str::uuid(),
            'conversation_type' => $this->faker->randomElement($types),
            'title' => function (array $attributes) {
                return $attributes['conversation_type'] === 'group'
                    ? $this->faker->words(3, true)
                    : null;
            },
            'project_id' => $this->faker->optional(0.3)->randomElement([Project::factory()]),
            'created_by' => User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
            'last_message_at' => function (array $attributes) {
                return $this->faker->optional(0.8)->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    public function direct(): static
    {
        return $this->state([
            'conversation_type' => 'direct',
            'title' => null,
        ]);
    }

    public function group(): static
    {
        return $this->state([
            'conversation_type' => 'group',
            'title' => $this->faker->words(3, true),
        ]);
    }

    public function forProject(Project $project): static
    {
        return $this->state([
            'project_id' => $project->id,
            'conversation_type' => 'group',
            'title' => $project->title . ' Chat',
        ]);
    }
}
