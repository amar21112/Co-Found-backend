<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    protected static array $categories = [
        'SaaS', 'Mobile App', 'E-Commerce', 'FinTech', 'HealthTech', 'EdTech',
        'Social Network', 'Marketplace', 'AI/ML', 'Blockchain', 'Game', 'IoT',
        'Cybersecurity', 'GreenTech', 'AgriTech', 'LegalTech', 'HR Tech',
    ];

    public function definition(): array
    {
        $title     = $this->faker->catchPhrase();
        $minSize   = $this->faker->numberBetween(2, 4);
        $maxSize   = $this->faker->numberBetween($minSize + 1, 10);
        $status    = $this->faker->randomElement(['planning', 'active', 'on_hold', 'completed', 'cancelled']);

        return [
            'id'                       => $this->faker->uuid(),
            'owner_id'                 => User::factory(),
            'title'                    => $title,
            'slug'                     => Str::slug($title) . '-' . $this->faker->numerify('###'),
            'short_description'        => $this->faker->sentence(12),
            'full_description'         => $this->faker->paragraphs(4, true),
            'category'                 => $this->faker->randomElement(self::$categories),
            'status'                   => $status,
            'visibility'               => $this->faker->randomElement(['public', 'private', 'unlisted']),
            'team_size_min'            => $minSize,
            'team_size_max'            => $maxSize,
            'current_team_size'        => $this->faker->numberBetween(1, $minSize),
            'start_date'               => $this->faker->dateTimeBetween('-6 months', '+1 month')->format('Y-m-d'),
            'target_completion_date'   => $this->faker->dateTimeBetween('+3 months', '+2 years')->format('Y-m-d'),
            'actual_completion_date'   => $status === 'completed' ? $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d') : null,
            'is_accepting_applications'=> $status === 'active' || $status === 'planning',
            'application_deadline'     => $this->faker->dateTimeBetween('+1 month', '+6 months')->format('Y-m-d'),
            'view_count'               => $this->faker->numberBetween(0, 5000),
            'application_count'        => $this->faker->numberBetween(0, 100),
            'published_at'             => $status !== 'planning' ? $this->faker->dateTimeBetween('-6 months', 'now') : null,
            'archived_at'              => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn() => [
            'status'                    => 'active',
            'is_accepting_applications' => true,
            'published_at'              => now()->subDays(rand(1, 90)),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn() => [
            'status'                    => 'completed',
            'is_accepting_applications' => false,
            'actual_completion_date'    => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
        ]);
    }
}
