<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use App\Models\Project;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        $types = ['harassment', 'spam', 'inappropriate', 'copyright', 'other'];
        $statuses = ['pending', 'under_review', 'resolved', 'dismissed', 'escalated'];
        $priorities = ['high', 'medium', 'low'];

        $type = $this->faker->randomElement($types);

        return [
            'id' => Str::uuid(),
            'reporter_id' => User::factory(),
            'reported_user_id' => User::factory(),
            'reported_content_type' => $this->faker->randomElement(['project', 'message', 'profile']),
            'reported_content_id' => Str::uuid(),
            'report_type' => $type,
            'description' => $this->faker->paragraph(),
            'evidence' => [
                'screenshots' => $this->faker->optional(0.5)->url(),
                'notes' => $this->faker->optional(0.3)->sentence(),
            ],
            'status' => $this->faker->randomElement($statuses),
            'priority' => $this->getPriorityForType($type),
            'assigned_to' => $this->faker->optional(0.3)->randomElement([User::factory()]),
            'resolved_by' => $this->faker->optional(0.2)->randomElement([User::factory()]),
            'resolution_action' => function (array $attributes) {
                return $attributes['status'] === 'resolved'
                    ? $this->faker->randomElement(['warned', 'suspended', 'content_removed', 'no_action'])
                    : null;
            },
            'resolution_notes' => function (array $attributes) {
                return $attributes['status'] === 'resolved'
                    ? $this->faker->optional(0.7)->paragraph()
                    : null;
            },
            'created_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
            'resolved_at' => function (array $attributes) {
                return in_array($attributes['status'], ['resolved', 'dismissed'])
                    ? $this->faker->dateTimeBetween($attributes['created_at'], 'now')
                    : null;
            },
        ];
    }

    private function getPriorityForType($type): string
    {
        $priorities = [
            'harassment' => 'high',
            'copyright' => 'high',
            'inappropriate' => 'medium',
            'spam' => 'medium',
            'other' => 'low'
        ];

        return $priorities[$type] ?? 'medium';
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'assigned_to' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_review',
            'assigned_to' => User::factory(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'resolved_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
            'report_type' => 'harassment',
        ]);
    }
}
