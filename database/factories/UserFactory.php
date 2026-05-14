<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\IdentityVerificationLevel;
use App\Enums\UserRole;
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
            'password'                    => Hash::make('password'),
            'full_name'                   => $this->faker->name(),

            // ── Profile picture ───────────────────────────────────────────────
            // Set to null by default.
            //
            // The previous value ($this->faker->imageUrl()) generated an external
            // HTTP URL. That breaks ProfilePictureService because toUrl() calls
            // asset('storage/' . $path), which would produce a nonsensical URL
            // for an http:// string.
            //
            // For tests that need a picture, use the withProfilePicture() state
            // which sets a realistic relative path, or upload a real file via
            // ProfilePictureService::store() in the test setup.
            //
            // Existing DB records with old HTTP URLs are handled gracefully:
            // ProfilePictureService::toUrl() detects isExternalUrl() and returns
            // the value unchanged, so NO database migration is needed.
            'profile_picture_url'         => null,

            'bio'                         => $this->faker->paragraph(),
            'location'                    => $this->faker->city() . ', ' . $this->faker->country(),
            'website_url'                 => $this->faker->url(),
            'linkedin_url'                => 'https://linkedin.com/in/' . $this->faker->userName(),
            'github_url'                  => 'https://github.com/' . $this->faker->userName(),
            'role'                        => UserRole::RegularUser->value,
            'account_status'              => AccountStatus::Active->value,
            'email_verified'              => true,
            'identity_verified'           => false,
            'identity_verification_level' => IdentityVerificationLevel::None->value,
            'email_verification_token'    => null,
            'email_verification_expires'  => null,
            'last_login_at'               => null,
            'last_login_ip'               => null,
            'login_attempts'              => 0,
            'locked_until'                => null,
            'deleted_at'                  => null,
        ];
    }

    // ── Role states ───────────────────────────────────────────────────────────

    public function admin(): static
    {
        return $this->state(fn() => [
            'role'           => UserRole::Administrator->value,
            'account_status' => AccountStatus::Active->value,
            'email_verified' => true,
        ]);
    }

    public function moderator(): static
    {
        return $this->state(fn() => [
            'role'           => UserRole::Moderator->value,
            'account_status' => AccountStatus::Active->value,
            'email_verified' => true,
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn() => [
            'email'          => 'guest_' . Str::uuid() . '@guest.cofound',
            'username'       => 'guest_' . Str::random(12),
            'password'       => Hash::make(Str::random(32)),
            'full_name'      => 'Guest',
            'role'           => UserRole::Guest->value,
            'account_status' => AccountStatus::Active->value,
            'email_verified' => false,
        ]);
    }

    // ── Account status states ─────────────────────────────────────────────────

    /**
     * Registered but email not yet verified.
     * Has a token, account_status=pending.
     */
    public function unverified(): static
    {
        return $this->state(fn() => [
            'role'                       => UserRole::RegularUser->value,
            'email_verified'             => false,
            'account_status'             => AccountStatus::Pending->value,
            'email_verification_token'   => Str::random(64),
            'email_verification_expires' => now()->addHours(24),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn() => [
            'account_status' => AccountStatus::Suspended->value,
        ]);
    }

    public function banned(): static
    {
        return $this->state(fn() => [
            'account_status' => AccountStatus::Banned->value,
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn() => [
            'account_status' => AccountStatus::Deleted->value,
        ]);
    }

    /**
     * Account currently locked due to too many failed login attempts.
     */
    public function locked(): static
    {
        return $this->state(fn() => [
            'login_attempts' => 5,
            'locked_until'   => now()->addMinutes(15),
        ]);
    }

    /**
     * Account whose lock period has already expired.
     * Useful for testing that expired locks are ignored.
     */
    public function hasLoggedIn(): static
    {
        return $this->state(fn() => [
            'last_login_at' => $this->faker->dateTimeBetween('-30 days'),
            'last_login_ip' => $this->faker->ipv4(),
        ]);
    }

    public function lockExpired(): static
    {
        return $this->state(fn() => [
            'login_attempts' => 5,
            'locked_until'   => now()->subMinute(),
        ]);
    }

    // ── Verification states ───────────────────────────────────────────────────

    public function identityVerified(): static
    {
        return $this->state(fn() => [
            'identity_verified'           => true,
            'identity_verification_level' => IdentityVerificationLevel::Advanced->value,
        ]);
    }

    public function fullyVerified(): static
    {
        return $this->state(fn() => [
            'email_verified'              => true,
            'account_status'              => AccountStatus::Active->value,
            'identity_verified'           => true,
            'identity_verification_level' => IdentityVerificationLevel::Advanced->value,
        ]);
    }

    // ── Profile picture state ─────────────────────────────────────────────────

    /**
     * Sets a fake but correctly-formatted relative storage path.
     *
     * Use this in tests that exercise picture URL resolution or deletion:
     *
     *   $user = User::factory()->withProfilePicture()->create();
     *   $user->profile_picture_url === "profile_pictures/{uuid}.jpg"
     *   UserResource resolves it to the full storage URL via asset().
     *
     * If the test also needs the file to physically exist on the fake disk:
     *   Storage::fake('public');
     *   $user = User::factory()->withProfilePicture()->create();
     *   Storage::disk('public')->put($user->profile_picture_url, 'fake-content');
     */
    public function withProfilePicture(): static
    {
        return $this->state(fn() => [
            'profile_picture_url' => 'profile_pictures/' . Str::uuid() . '.jpg',
        ]);
    }
}
