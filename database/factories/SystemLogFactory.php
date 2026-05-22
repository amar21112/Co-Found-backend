<?php

namespace Database\Factories;

use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemLog>
 */
class SystemLogFactory extends Factory
{
    protected $model = SystemLog::class;

    public function definition(): array
    {
        return [
            'id'         => $this->faker->uuid(),
            'log_level'  => $this->faker->randomElement(['debug', 'info', 'warning', 'error', 'critical']),
            'component'  => $this->faker->randomElement(['auth', 'payments', 'ml', 'chat', 'api']),
            'event_type' => $this->faker->slug(2),
            'message'    => $this->faker->sentence(),
            'details'    => null,
            'ip_address' => $this->faker->ipv4(),
            'user_id'    => null,
        ];
    }
}
