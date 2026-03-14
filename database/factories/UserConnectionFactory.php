<?php

namespace Database\Factories;

use App\Models\UserConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserConnectionFactory extends Factory
{
    protected $model = UserConnection::class;

    public function definition(): array
    {
        return [
            'id'               => $this->faker->uuid(),
            'requester_id'     => User::factory(),
            'recipient_id'     => User::factory(),
            'status'           => $this->faker->randomElement(['pending', 'accepted', 'rejected', 'blocked']),
            'connection_type'  => $this->faker->randomElement(['co_founder', 'collaborator', 'mentor', 'mentee', null]),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn() => ['status' => 'accepted']);
    }
}
