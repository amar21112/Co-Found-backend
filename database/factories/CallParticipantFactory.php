<?php

namespace Database\Factories;

use App\Models\CallParticipant;
use App\Models\VideoCall;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CallParticipantFactory extends Factory
{
    protected $model = CallParticipant::class;

    public function definition(): array
    {
        $joinedAt = $this->faker->optional(0.8)->dateTimeBetween('-2 hours', 'now');
        $leftAt = $joinedAt && $this->faker->boolean(70)
            ? $this->faker->dateTimeBetween($joinedAt, $joinedAt->format('Y-m-d H:i:s') . ' +1 hour')
            : null;

        return [
            'id' => Str::uuid(),
            'call_id' => VideoCall::factory(),
            'user_id' => User::factory(),
            'joined_at' => $joinedAt,
            'left_at' => $leftAt,
            'duration_seconds' => $joinedAt && $leftAt ? $joinedAt->diffInSeconds($leftAt) : null,
            'role' => $this->faker->randomElement(['host', 'participant']),
        ];
    }

    public function host(): static
    {
        return $this->state([
            'role' => 'host',
        ]);
    }

    public function present(): static
    {
        return $this->state([
            'joined_at' => $this->faker->dateTimeBetween('-30 minutes', 'now'),
            'left_at' => null,
            'duration_seconds' => null,
        ]);
    }

    public function left(): static
    {
        $joinedAt = $this->faker->dateTimeBetween('-2 hours', '-30 minutes');
        $leftAt = $this->faker->dateTimeBetween($joinedAt, $joinedAt->format('Y-m-d H:i:s') . ' +1 hour');

        return $this->state([
            'joined_at' => $joinedAt,
            'left_at' => $leftAt,
            'duration_seconds' => $joinedAt->diffInSeconds($leftAt),
        ]);
    }
}
