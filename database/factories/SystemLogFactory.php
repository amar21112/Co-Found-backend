<?php

namespace Database\Factories;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SystemLogFactory extends Factory
{
    protected $model = SystemLog::class;

    public function definition(): array
    {
        $levels = ['debug', 'info', 'warning', 'error', 'critical'];
        $components = ['auth', 'api', 'database', 'cache', 'queue', 'mail', 'storage', 'websocket'];

        return [
            'id' => Str::uuid(),
            'log_level' => $this->faker->randomElement($levels),
            'component' => $this->faker->randomElement($components),
            'event_type' => $this->faker->word(),
            'message' => $this->faker->sentence(),
            'details' => [
                'file' => $this->faker->filePath(),
                'line' => $this->faker->numberBetween(1, 1000),
                'trace' => $this->faker->optional(0.3)->text(),
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_id' => $this->faker->optional(0.3)->randomElement([User::factory()]),
            'created_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }

    public function debug(): static
    {
        return $this->state([
            'log_level' => 'debug',
        ]);
    }

    public function info(): static
    {
        return $this->state([
            'log_level' => 'info',
        ]);
    }

    public function warning(): static
    {
        return $this->state([
            'log_level' => 'warning',
        ]);
    }

    public function error(): static
    {
        return $this->state([
            'log_level' => 'error',
        ]);
    }

    public function critical(): static
    {
        return $this->state([
            'log_level' => 'critical',
        ]);
    }

    public function forComponent($component): static
    {
        return $this->state([
            'component' => $component,
        ]);
    }
}
