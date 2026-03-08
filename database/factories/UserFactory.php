<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $roles = ['guest', 'regular_user', 'project_owner', 'moderator', 'administrator'];
        $statuses = ['pending', 'active', 'suspended', 'banned', 'deleted'];

        return [
            'id' => Str::uuid(),
            'email' => $this->faker->unique()->safeEmail(),
            'username' => $this->faker->unique()->userName(),
            'password_hash' => bcrypt('password'),
            'full_name' => $this->faker->name(),
            'profile_picture_url' => $this->faker->optional(0.7)->imageUrl(300, 300, 'people'),
            'bio' => $this->faker->optional(0.8)->paragraphs(3, true),
            'location' => $this->faker->optional(0.6)->city() . ', ' . $this->faker->optional(0.6)->country(),
            'website_url' => $this->faker->optional(0.3)->url(),
            'linkedin_url' => $this->faker->optional(0.4)->url(),
            'github_url' => $this->faker->optional(0.4)->url(),
            'role' => $this->faker->randomElement($roles),
            'account_status' => $this->faker->randomElement($statuses),
            'email_verified' => $this->faker->boolean(80),
            'identity_verified' => $this->faker->boolean(60),
            'identity_verification_level' => $this->faker->optional(0.5)->randomElement(['basic', 'advanced']),
            'email_verification_token' => $this->faker->optional(0.2)->sha256(),
            'email_verification_expires' => $this->faker->optional(0.2)->dateTimeBetween('now', '+2 days'),
            'last_login_at' => $this->faker->optional(0.9)->dateTimeBetween('-30 days', 'now'),
            'last_login_ip' => $this->faker->ipv4(),
            'login_attempts' => $this->faker->numberBetween(0, 5),
            'locked_until' => $this->faker->optional(0.05)->dateTimeBetween('now', '+1 hour'),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'administrator',
            'email_verified' => true,
            'identity_verified' => true,
        ]);
    }

    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'moderator',
            'email_verified' => true,
        ]);
    }

    public function projectOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'project_owner',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_status' => 'active',
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'identity_verified' => true,
            'identity_verification_level' => 'advanced',
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified' => false,
            'identity_verified' => false,
        ]);
    }
}
