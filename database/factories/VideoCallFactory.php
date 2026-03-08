<?php

namespace Database\Factories;

use App\Models\VideoCall;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VideoCallFactory extends Factory
{
    protected $model = VideoCall::class;

    public function definition(): array
    {
        $types = ['direct', 'group'];
        $statuses = ['scheduled', 'active', 'ended', 'cancelled'];

        return [
            'id' => Str::uuid(),
            'call_type' => $this->faker->randomElement($types),
            'conversation_id' => $this->faker->optional(0.7)->randomElement([Conversation::factory()]),
            'project_id' => $this->faker->optional(0.3)->randomElement([Project::factory()]),
            'initiated_by' => User::factory(),
            'room_name' => 'room-' . Str::random(10),
            'room_url' => $this->faker->url() . '/call/' . Str::random(10),
            'start_time' => $this->faker->optional(0.7)->dateTimeBetween('-1 week', '+1 week'),
            'end_time' => function (array $attributes) {
                return $attributes['start_time'] && $this->faker->boolean(70)
                    ? $this->faker->dateTimeBetween($attributes['start_time'], $attributes['start_time']->format('Y-m-d H:i:s') . ' +2 hours')
                    : null;
            },
            'duration_seconds' => function (array $attributes) {
                return $attributes['start_time'] && $attributes['end_time']
                    ? $attributes['start_time']->diffInSeconds($attributes['end_time'])
                    : null;
            },
            'status' => $this->faker->randomElement($statuses),
            'recording_url' => $this->faker->optional(0.2)->url(),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'start_time' => $this->faker->dateTimeBetween('+1 hour', '+1 week'),
            'end_time' => null,
            'duration_seconds' => null,
        ]);
    }

    public function active(): static
    {
        $startTime = $this->faker->dateTimeBetween('-30 minutes', 'now');

        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_time' => $startTime,
            'end_time' => null,
            'duration_seconds' => null,
        ]);
    }

    public function ended(): static
    {
        $startTime = $this->faker->dateTimeBetween('-2 hours', '-30 minutes');
        $endTime = $this->faker->dateTimeBetween($startTime, $startTime->format('Y-m-d H:i:s') . ' +1 hour');

        return $this->state(fn (array $attributes) => [
            'status' => 'ended',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_seconds' => $startTime->diffInSeconds($endTime),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'start_time' => null,
            'end_time' => null,
        ]);
    }
}
