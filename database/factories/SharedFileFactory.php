<?php

namespace Database\Factories;

use App\Models\SharedFile;
use App\Models\File;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SharedFileFactory extends Factory
{
    protected $model = SharedFile::class;

    public function definition(): array
    {
        $permissionLevels = ['view', 'download', 'edit'];

        return [
            'id' => Str::uuid(),
            'file_id' => File::factory(),
            'conversation_id' => $this->faker->optional(0.5)->randomElement([Conversation::factory()]),
            'message_id' => $this->faker->optional(0.3)->randomElement([Message::factory()]),
            'shared_by' => User::factory(),
            'permission_level' => $this->faker->randomElement($permissionLevels),
            'expires_at' => $this->faker->optional(0.3)->dateTimeBetween('+1 day', '+1 month'),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function inConversation(): static
    {
        return $this->state([
            'conversation_id' => Conversation::factory(),
            'message_id' => null,
        ]);
    }

    public function inMessage(): static
    {
        return $this->state([
            'conversation_id' => null,
            'message_id' => Message::factory(),
        ]);
    }

    public function viewOnly(): static
    {
        return $this->state([
            'permission_level' => 'view',
        ]);
    }

    public function downloadable(): static
    {
        return $this->state([
            'permission_level' => 'download',
        ]);
    }

    public function expiring(): static
    {
        return $this->state([
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+1 week'),
        ]);
    }
}
