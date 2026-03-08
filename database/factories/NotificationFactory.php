<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $types = ['message', 'application_update', 'connection_request', 'project_update', 'mention', 'system', 'collaboration_invite'];
        $priorities = ['high', 'normal', 'low'];

        $type = $this->faker->randomElement($types);
        $read = $this->faker->boolean(70);

        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'type' => $type,
            'title' => $this->getTitleForType($type),
            'body' => $this->getBodyForType($type),
            'data' => $this->getDataForType($type),
            'priority' => $this->getPriorityForType($type),
            'read' => $read,
            'read_at' => $read ? $this->faker->dateTimeBetween('-2 days', 'now') : null,
            'delivered_at' => $this->faker->optional(0.9)->dateTimeBetween('-2 days', 'now'),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    private function getTitleForType($type): string
    {
        $titles = [
            'message' => 'New Message',
            'application_update' => 'Application Status Update',
            'connection_request' => 'New Connection Request',
            'project_update' => 'Project Update',
            'mention' => 'You were mentioned',
            'system' => 'System Notification',
            'collaboration_invite' => 'Collaboration Invitation'
        ];

        return $titles[$type] ?? 'Notification';
    }

    private function getBodyForType($type): string
    {
        $bodies = [
            'message' => 'You have a new message from ' . $this->faker->name(),
            'application_update' => 'Your application for "' . $this->faker->words(3, true) . '" has been ' . $this->faker->randomElement(['accepted', 'rejected', 'reviewed']),
            'connection_request' => $this->faker->name() . ' wants to connect with you',
            'project_update' => $this->faker->words(3, true) . ' has reached a new milestone',
            'mention' => $this->faker->name() . ' mentioned you in a comment',
            'system' => 'Platform maintenance scheduled for ' . $this->faker->dateTimeBetween('+1 day', '+1 week')->format('M d, Y'),
            'collaboration_invite' => $this->faker->name() . ' invited you to collaborate on ' . $this->faker->words(3, true)
        ];

        return $bodies[$type] ?? 'You have a new notification';
    }

    private function getDataForType($type): array
    {
        $data = [];

        switch ($type) {
            case 'message':
                $data['sender_id'] = User::factory()->create()->id;
                $data['conversation_id'] = Str::uuid();
                break;
            case 'application_update':
                $data['project_id'] = Str::uuid();
                $data['status'] = $this->faker->randomElement(['accepted', 'rejected', 'reviewing']);
                break;
            case 'connection_request':
                $data['requester_id'] = User::factory()->create()->id;
                break;
            case 'mention':
                $data['mentioned_by'] = User::factory()->create()->id;
                $data['context'] = $this->faker->sentence();
                break;
        }

        return $data;
    }

    private function getPriorityForType($type): string
    {
        $priorities = [
            'message' => 'normal',
            'application_update' => 'normal',
            'connection_request' => 'normal',
            'project_update' => 'low',
            'mention' => 'high',
            'system' => 'high',
            'collaboration_invite' => 'high'
        ];

        return $priorities[$type] ?? 'normal';
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read' => false,
            'read_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read' => true,
            'read_at' => $this->faker->dateTimeBetween('-2 days', 'now'),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }
}
