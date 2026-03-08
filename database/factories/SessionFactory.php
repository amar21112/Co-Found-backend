<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SessionFactory extends Factory
{
    protected $model = Session::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'session_token' => Str::random(80),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'device_info' => json_encode([
                'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                'os' => $this->faker->randomElement(['Windows', 'MacOS', 'Linux', 'iOS', 'Android']),
                'mobile' => $this->faker->boolean(20)
            ]),
            'expires_at' => $this->faker->dateTimeBetween('+1 hour', '+7 days'),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-7 days', '-1 hour'),
        ]);
    }

    public function mobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_info' => json_encode([
                'browser' => 'Mobile Safari',
                'os' => 'iOS',
                'mobile' => true
            ]),
        ]);
    }
}
