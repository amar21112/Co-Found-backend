<?php

namespace Database\Factories;

use App\Models\ContentModeration;
use App\Models\User;
use App\Models\Project;
use App\Models\Message;
use App\Models\PortfolioItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContentModerationFactory extends Factory
{
    protected $model = ContentModeration::class;

    public function definition(): array
    {
        $moderationTypes = ['reported', 'auto_flagged', 'random_sampling', 'targeted'];
        $actions = ['approved', 'edited', 'removed', 'quarantined', 'escalated'];
        $contentTypes = ['project', 'message', 'portfolio_item', 'user_profile'];

        $contentType = $this->faker->randomElement($contentTypes);
        $action = $this->faker->randomElement($actions);

        return [
            'id' => Str::uuid(),
            'moderator_id' => User::factory(),
            'content_type' => $contentType,
            'content_id' => Str::uuid(),
            'moderation_type' => $this->faker->randomElement($moderationTypes),
            'original_content' => $this->faker->paragraphs(2, true),
            'moderated_content' => $action === 'edited' ? $this->faker->paragraphs(2, true) : null,
            'action_taken' => $action,
            'reason' => $this->faker->optional(0.8)->sentence(),
            'guideline_referenced' => $this->faker->optional(0.6)->words(3, true),
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'action_taken' => 'approved',
            'moderated_content' => null,
        ]);
    }

    public function edited(): static
    {
        return $this->state([
            'action_taken' => 'edited',
            'moderated_content' => $this->faker->paragraphs(2, true),
        ]);
    }

    public function removed(): static
    {
        return $this->state([
            'action_taken' => 'removed',
            'moderated_content' => null,
        ]);
    }

    public function reported(): static
    {
        return $this->state([
            'moderation_type' => 'reported',
        ]);
    }

    public function autoFlagged(): static
    {
        return $this->state([
            'moderation_type' => 'auto_flagged',
        ]);
    }
}
