<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id'                          => $this->faker->uuid(),
            'email'                       => $this->faker->unique()->safeEmail(),
            'username'                    => $this->faker->unique()->userName(),
            'password'               => Hash::make('password'),
            'full_name'                   => $this->faker->name(),
            'profile_picture_url'         => $this->faker->imageUrl(200, 200, 'people'),
            'bio'                         => $this->faker->paragraph(),
            'location'                    => $this->faker->city() . ', ' . $this->faker->country(),
            'website_url'                 => $this->faker->url(),
            'linkedin_url'                => 'https://linkedin.com/in/' . $this->faker->userName(),
            'github_url'                  => 'https://github.com/' . $this->faker->userName(),
            'role'                        => 'regular_user',
            'account_status'              => 'active',
            'email_verified'              => true,
            'identity_verified'           => false,
            'identity_verification_level' => 'none',
            'email_verification_token'    => null,
            'email_verification_expires'  => null,
            'last_login_at'               => $this->faker->dateTimeBetween('-30 days', 'now'),
            'last_login_ip'               => $this->faker->ipv4(),
            'login_attempts'              => 0,
            'locked_until'                => null,
            'deleted_at'                  => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn() => ['role' => 'administrator', 'account_status' => 'active']);
    }

    public function moderator(): static
    {
        return $this->state(fn() => ['role' => 'moderator', 'account_status' => 'active']);
    }

    public function unverified(): static
    {
        return $this->state(fn() => [
            'email_verified'             => false,
            'account_status'             => 'pending',
            'email_verification_token'   => Str::random(64),
            'email_verification_expires' => now()->addHours(24),
        ]);
    }

    public function identityVerified(): static
    {
        return $this->state(fn() => [
            'identity_verified'           => true,
            'identity_verification_level' => 'advanced',
        ]);
    }
}
