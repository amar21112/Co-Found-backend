<?php

namespace Database\Factories;

use App\Models\AdminAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminActionFactory extends Factory
{
    protected $model = AdminAction::class;

    public function definition(): array
    {
        return [
            'id'           => $this->faker->uuid(),
            'admin_id'     => User::factory()->admin(),
            'action_type'  => $this->faker->randomElement([
                'user_suspended', 'user_banned', 'user_restored',
                'content_removed', 'project_archived', 'verification_approved',
                'verification_rejected', 'report_resolved', 'setting_updated',
            ]),
            'target_type'  => $this->faker->randomElement(['user', 'project', 'message', 'report', 'verification']),
            'target_id'    => $this->faker->uuid(),
            'details'      => json_encode(['note' => $this->faker->sentence(), 'previous_status' => 'active']),
            'ip_address'   => $this->faker->ipv4(),
        ];
    }
}
