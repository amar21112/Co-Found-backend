<?php

namespace Database\Factories;

use App\Enums\CallParticipantRole;
use App\Models\CallParticipant;
use App\Models\User;
use App\Models\VideoCall;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;

class CallParticipantFactory extends Factory
{
    protected $model = CallParticipant::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        $joinedAt = $this->faker->dateTimeBetween('-2 hours');
        $hasLeft  = $this->faker->boolean(30);
        $leftAt   = $hasLeft
            ? (clone $joinedAt)->modify('+' . $this->faker->numberBetween(60, 3600) . ' seconds')
            : null;

        return [
            'id'               => $this->faker->uuid(),
            'call_id'          => VideoCall::factory(),
            'user_id'          => User::factory(),
            'role'             => CallParticipantRole::Participant->value,
            'joined_at'        => $joinedAt,
            'left_at'          => $leftAt,
            'duration_seconds' => $leftAt
                ? (new DateTime($leftAt->format('Y-m-d H:i:s')))->diff(new DateTime($joinedAt->format('Y-m-d H:i:s')))->s
                : null,
        ];
    }

    // ── States ────────────────────────────────────────────────────────────────

    public function host(): static
    {
        return $this->state(fn() => [
            'role' => CallParticipantRole::Host->value,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn() => [
            'joined_at'        => now()->subMinutes(5),
            'left_at'          => null,
            'duration_seconds' => null,
        ]);
    }

    public function left(): static
    {
        return $this->state(function () {
            $duration = $this->faker->numberBetween(60, 3600);
            return [
                'joined_at'        => now()->subSeconds($duration + 30),
                'left_at'          => now()->subSeconds(30),
                'duration_seconds' => $duration,
            ];
        });
    }
}
