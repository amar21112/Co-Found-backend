<?php

namespace Database\Factories;

use App\Models\UserConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserConnectionFactory extends Factory
{
    protected $model = UserConnection::class;

    public function definition(): array
    {
        $statuses = ['pending', 'accepted', 'rejected', 'blocked'];
        $types = ['collaborator', 'mentor', 'mentee', 'friend'];

        return [
            'id' => Str::uuid(),
            'requester_id' => User::factory(),
            'recipient_id' => User::factory(),
            'status' => $this->faker->randomElement($statuses),
            'connection_type' => $this->faker->optional(0.6)->randomElement($types),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'blocked',
        ]);
    }
}
