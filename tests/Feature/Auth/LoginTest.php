<?php

namespace Tests\Feature\Auth;

use App\Enums\AccountStatus;
use App\Enums\RestrictionType;
use App\Models\User;
use App\Models\UserRestriction;
use Illuminate\Support\Facades\Hash;

class LoginTest extends AuthTestCase
{
    /** @test */
    public function login_returns_token_for_valid_credentials(): void
    {
        $this->makeActiveUser(['email' => 'active@example.com']);

        $this->postJson('/api/auth/login', [
            'email' => 'active@example.com', 'password' => 'Secret123',
        ])->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => ['access_token', 'token_type', 'user'],
            ]);
    }

    /** @test */
    public function login_updates_last_login_metadata(): void
    {
        $user = $this->makeActiveUser([
            'email'         => 'active@example.com',
            'last_login_at' => null,
            'last_login_ip' => null,
        ]);
        $this->assertNull($user->last_login_at);

        $this->postJson('/api/auth/login', [
            'email' => 'active@example.com', 'password' => 'Secret123',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->last_login_ip);
    }

    /** @test */
    public function login_resets_attempt_counter_on_success(): void
    {
        $this->makeActiveUser([
            'email'          => 'active@example.com',
            'login_attempts' => 3,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'active@example.com', 'password' => 'Secret123',
        ])->assertStatus(200);

        $this->assertEquals(0, User::where('email', 'active@example.com')->value('login_attempts'));
    }

    /** @test */
    public function login_revokes_all_previous_tokens_on_success(): void
    {
        $user = $this->makeActiveUser(['email' => 'active@example.com']);
        $user->createToken('old_token_1');
        $user->createToken('old_token_2');
        $this->assertDatabaseCount('personal_access_tokens', 2);

        $this->postJson('/api/auth/login', [
            'email' => 'active@example.com', 'password' => 'Secret123',
        ])->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    /** @test */
    public function login_allows_pending_user_with_unverified_email(): void
    {
        $this->makePendingUser(['email' => 'pending@example.com']);

        $this->postJson('/api/auth/login', [
            'email' => 'pending@example.com', 'password' => 'Secret123',
        ])->assertStatus(200)
            ->assertJsonPath('data.user.account_status', AccountStatus::Pending->value);
    }

    /** @test */
    public function login_fails_with_wrong_password(): void
    {
        $this->makeActiveUser(['email' => 'active@example.com']);

        $this->postJson('/api/auth/login', [
            'email' => 'active@example.com', 'password' => 'WrongPass1',
        ])->assertStatus(401)->assertJsonPath('status', 'error');
    }

    /** @test */
    public function login_fails_for_non_existent_email(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com', 'password' => 'Secret123',
        ])->assertStatus(401);
    }

    /** @test */
    public function login_increments_attempt_counter_on_wrong_password(): void
    {
        $user = $this->makeActiveUser(['email' => 'active@example.com']);
        $this->assertEquals(0, $user->login_attempts);

        $this->postJson('/api/auth/login', [
            'email' => 'active@example.com', 'password' => 'WrongPass1',
        ])->assertStatus(401);

        $this->assertEquals(1, $user->fresh()->login_attempts);
    }

    /** @test */
    public function login_blocks_suspended_account(): void
    {
        User::factory()->suspended()->create([
            'email'    => 'suspended@example.com',
            'password' => Hash::make('Secret123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'suspended@example.com', 'password' => 'Secret123',
        ])->assertStatus(403);
    }

    /** @test */
    public function login_blocks_banned_account(): void
    {
        User::factory()->banned()->create([
            'email'    => 'banned@example.com',
            'password' => Hash::make('Secret123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'banned@example.com', 'password' => 'Secret123',
        ])->assertStatus(403);
    }

    /** @test */
    public function login_locks_account_after_max_failed_attempts(): void
    {
        $this->makeActiveUser(['email' => 'active@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'active@example.com', 'password' => 'WrongPass1',
            ]);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'active@example.com', 'password' => 'Secret123',
        ])->assertStatus(423)
            ->assertJsonStructure(['status', 'message', 'locked_until']);
    }

    /** @test */
    public function login_blocked_by_active_admin_restriction(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Secret123')]);

        UserRestriction::create([
            'user_id'          => $user->id,
            'restricted_by'    => $user->id,
            'restriction_type' => RestrictionType::FullSuspension->value,
            'reason'           => 'Spam behaviour',
            'is_active'        => true,
            'starts_at'        => now()->subHour(),
            'expires_at'       => now()->addDay(),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email, 'password' => 'Secret123',
        ])->assertStatus(403)
            ->assertJsonFragment(['message' => 'Your account has been restricted by an administrator. Please contact support.']);
    }

    /** @test */
    public function login_succeeds_when_admin_restriction_is_expired(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Secret123')]);

        UserRestriction::create([
            'user_id'          => $user->id,
            'restricted_by'    => $user->id,
            'restriction_type' => RestrictionType::FullSuspension->value,
            'reason'           => 'Old restriction',
            'is_active'        => true,
            'starts_at'        => now()->subDays(2),
            'expires_at'       => now()->subHour(),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email, 'password' => 'Secret123',
        ])->assertStatus(200);
    }

    /** @test */
    public function login_succeeds_with_non_login_blocking_restriction(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Secret123')]);

        UserRestriction::create([
            'user_id'          => $user->id,
            'restricted_by'    => $user->id,
            'restriction_type' => RestrictionType::MessagingBan->value,
            'reason'           => 'Spam messages',
            'is_active'        => true,
            'starts_at'        => now()->subHour(),
            'expires_at'       => now()->addDay(),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email, 'password' => 'Secret123',
        ])->assertStatus(200);
    }

    /** @test */
    public function login_succeeds_after_brute_force_lock_expires(): void
    {
        User::factory()->lockExpired()->create([
            'email'    => 'active@example.com',
            'password' => Hash::make('Secret123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'active@example.com', 'password' => 'Secret123',
        ])->assertStatus(200);
    }

    /** @test */
    public function login_fails_with_missing_fields(): void
    {
        $this->postJson('/api/auth/login')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
