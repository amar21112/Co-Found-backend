<?php

namespace Database\Factories;

use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Models\User;
use App\Models\VideoCall;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VideoCallFactory extends Factory
{
    protected $model = VideoCall::class;

    public function definition(): array
    {
        $status    = $this->faker->randomElement(array_column(CallStatus::cases(), 'value'));
        $startTime = $this->faker->dateTimeBetween('-30 days', '+7 days');
        $duration  = $status === CallStatus::Ended->value
            ? $this->faker->numberBetween(60, 7200)
            : null;
        $roomName  = Str::random();

        return [
            'id'               => $this->faker->uuid(),
            'call_type'        => $this->faker->randomElement(array_column(CallType::cases(), 'value')),
            // Default is adhoc — no conversation or project context.
            // Use forConversation() / forProject() states to add context.
            'conversation_id'  => null,
            'project_id'       => null,
            'initiated_by'     => User::factory(),
            'room_name'        => $roomName,
            'room_url'         => 'https://meet.jit.si/cofound-' . $roomName,
            'start_time'       => $startTime,
            'end_time'         => $duration
                ? (clone $startTime)->modify("+$duration seconds")
                : null,
            'duration_seconds' => $duration,
            'status'           => $status,
            'recording_url'    => $status === CallStatus::Ended->value && $this->faker->boolean(30)
                ? $this->faker->url()
                : null,
        ];
    }

    // ── States ────────────────────────────────────────────────────────────────

    public function scheduled(): static
    {
        return $this->state(fn() => [
            'status'     => CallStatus::Scheduled->value,
            'start_time' => now()->addMinutes(30),
            'end_time'   => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn() => [
            'status'     => CallStatus::Active->value,
            'start_time' => now()->subMinutes(5),
            'end_time'   => null,
        ]);
    }

    public function ended(): static
    {
        return $this->state(function () {
            $duration = $this->faker->numberBetween(60, 7200);
            return [
                'status'           => CallStatus::Ended->value,
                'start_time'       => now()->subSeconds($duration + 30),
                'end_time'         => now()->subSeconds(30),
                'duration_seconds' => $duration,
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn() => [
            'status'   => CallStatus::Cancelled->value,
            'end_time' => null,
        ]);
    }

    public function forProject(string $projectId): static
    {
        return $this->state(fn() => [
            'project_id'      => $projectId,
            'conversation_id' => null,
        ]);
    }

    public function forConversation(string $conversationId): static
    {
        return $this->state(fn() => [
            'conversation_id' => $conversationId,
            'project_id'      => null,
        ]);
    }

    public function adhoc(): static
    {
        return $this->state(fn() => [
            'project_id'      => null,
            'conversation_id' => null,
        ]);
    }
}
