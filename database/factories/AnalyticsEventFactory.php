<?php

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AnalyticsEventFactory extends Factory
{
    protected $model = AnalyticsEvent::class;

    public function definition(): array
    {
        $eventTypes = [
            'page_view', 'project_view', 'profile_view', 'search',
            'application_submitted', 'message_sent', 'connection_request',
            'login', 'registration', 'project_created'
        ];

        return [
            'id' => Str::uuid(),
            'event_type' => $this->faker->randomElement($eventTypes),
            'user_id' => $this->faker->optional(0.7)->randomElement([User::factory()]),
            'session_id' => Str::random(40),
            'properties' => $this->generateProperties(),
            'page_url' => $this->faker->url(),
            'referrer_url' => $this->faker->optional(0.5)->url(),
            'user_agent' => $this->faker->userAgent(),
            'ip_address' => $this->faker->ipv4(),
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }

    private function generateProperties(): array
    {
        $properties = [];

        if ($this->faker->boolean(30)) {
            $properties['project_id'] = Str::uuid();
        }

        if ($this->faker->boolean(20)) {
            $properties['search_query'] = $this->faker->words(3, true);
        }

        if ($this->faker->boolean(15)) {
            $properties['duration_seconds'] = $this->faker->numberBetween(5, 300);
        }

        return $properties;
    }

    public function pageView(): static
    {
        return $this->state([
            'event_type' => 'page_view',
        ]);
    }

    public function projectView(): static
    {
        return $this->state([
            'event_type' => 'project_view',
            'properties' => ['project_id' => Str::uuid()],
        ]);
    }

    public function search(): static
    {
        return $this->state([
            'event_type' => 'search',
            'properties' => ['search_query' => $this->faker->words(3, true)],
        ]);
    }

    public function authenticated(): static
    {
        return $this->state([
            'user_id' => User::factory(),
        ]);
    }

    public function anonymous(): static
    {
        return $this->state([
            'user_id' => null,
        ]);
    }
}
