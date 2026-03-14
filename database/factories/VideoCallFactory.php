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
        $status   = $this->faker->randomElement(['scheduled', 'active', 'ended', 'cancelled']);
        $start    = $this->faker->dateTimeBetween('-30 days', '+7 days');
        $duration = $status === 'ended' ? $this->faker->numberBetween(300, 7200) : null;

        return [
            'id'               => $this->faker->uuid(),
            'call_type'        => $this->faker->randomElement(['direct', 'group']),
            'conversation_id'  => Conversation::factory(),
            'project_id'       => $this->faker->boolean(60) ? Project::factory() : null,
            'initiated_by'     => User::factory(),
            'room_name'        => 'room-' . Str::random(12),
            'room_url'         => 'https://meet.cofound.io/room-' . Str::random(12),
            'start_time'       => $start,
            'end_time'         => $status === 'ended' ? (clone $start)->modify("+{$duration} seconds") : null,
            'duration_seconds' => $duration,
            'status'           => $status,
            'recording_url'    => $status === 'ended' && $this->faker->boolean(30) ? $this->faker->url() : null,
        ];
    }
}
