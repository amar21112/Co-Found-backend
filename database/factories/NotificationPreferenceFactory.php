<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'id'                    => $this->faker->uuid(),
            'user_id'               => User::factory(),
            'platform_notifications'=> true,
            'email_notifications'   => true,
            'push_notifications'    => true,
            'notification_digest'   => 'immediate',
            'quiet_hours_start'     => null,
            'quiet_hours_end'       => null,
            'quiet_hours_timezone'  => null,
            'preferences'           => json_encode([]),
        ];
    }
}
