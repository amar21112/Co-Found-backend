<?php

namespace Database\Factories;

use App\Models\CollaborationInvitation;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollaborationInvitationFactory extends Factory
{
    protected $model = CollaborationInvitation::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'accepted', 'rejected', 'expired']);

        return [
            'id'               => $this->faker->uuid(),
            'sender_id'        => User::factory(),
            'recipient_id'     => User::factory(),
            'project_id'       => $this->faker->boolean(70) ? Project::factory() : null,
            'invitation_type'  => $this->faker->randomElement(['project_join', 'team_invite', 'collaboration_request', 'mentorship']),
            'role'             => $this->faker->randomElement(['Developer', 'Designer', 'CTO', 'CMO', 'Advisor', null]),
            'message'          => $this->faker->paragraph(),
            'status'           => $status,
            'expires_at'       => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'responded_at'     => in_array($status, ['accepted', 'rejected']) ? $this->faker->dateTimeBetween('-14 days', 'now') : null,
            'response_message' => in_array($status, ['accepted', 'rejected']) ? $this->faker->sentence() : null,
        ];
    }
}
