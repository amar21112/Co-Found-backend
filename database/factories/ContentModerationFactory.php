<?php

namespace Database\Factories;

use App\Models\ContentModeration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentModerationFactory extends Factory
{
    protected $model = ContentModeration::class;

    public function definition(): array
    {
        return [
            'id'                   => $this->faker->uuid(),
            'moderator_id'         => User::factory()->moderator(),
            'content_type'         => $this->faker->randomElement(['message', 'project', 'user_profile', 'portfolio_item', 'other']),
            'content_id'           => $this->faker->uuid(),
            'moderation_type'      => $this->faker->randomElement(['reported', 'auto_flagged', 'random_sampling', 'targeted']),
            'original_content'     => $this->faker->paragraph(),
            'moderated_content'    => null,
            'action_taken'         => $this->faker->randomElement(['approved', 'edited', 'removed', 'quarantined', 'escalated']),
            'reason'               => $this->faker->sentence(),
            'guideline_referenced' => null,
        ];
    }
}
