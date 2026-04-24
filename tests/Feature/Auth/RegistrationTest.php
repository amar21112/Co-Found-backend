<?php

namespace Tests\Feature\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RegistrationTest extends AuthTestCase
{
    /** @test */
    public function register_creates_pending_user_and_returns_token(): void
    {
        Log::spy();

        $response = $this->postJson('/api/v1/auth/register', $this->registerPayload());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['access_token', 'token_type',
                    'user' => ['id', 'email', 'username', 'role', 'account_status']],
            ])
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.email', 'john@example.com')
            ->assertJsonPath('data.user.role', UserRole::RegularUser->value)
            ->assertJsonPath('data.user.account_status', AccountStatus::Pending->value);

        $this->assertDatabaseHas('users', [
            'email'          => 'john@example.com',
            'username'       => 'johndoe',
            'account_status' => AccountStatus::Pending->value,
            'email_verified' => false,
            'role'           => UserRole::RegularUser->value,
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    /** @test */
    public function register_dispatches_verification_email(): void
    {
        Log::spy();

        $this->postJson('/api/v1/auth/register', $this->registerPayload())->assertStatus(201);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->email_verification_token);
        $this->assertTrue($user->email_verification_expires->isFuture());

        Log::shouldHaveReceived('info')->once()
            ->with('[Co-Found] Email verification token', \Mockery::type('array'));
    }

    /** @test */
    public function register_fails_with_duplicate_email(): void
    {
        $this->makeActiveUser(['email' => 'john@example.com']);

        $this->postJson('/api/v1/auth/register', $this->registerPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function register_fails_with_duplicate_username(): void
    {
        $this->makeActiveUser(['username' => 'johndoe']);

        $this->postJson('/api/v1/auth/register', $this->registerPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    /** @test */
    public function register_fails_with_no_uppercase_in_password(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registerPayload([
            'password'              => 'allowercase1',
            'password_confirmation' => 'allowercase1',
        ]))->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function register_fails_with_no_digit_in_password(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registerPayload([
            'password'              => 'NoDigitHere',
            'password_confirmation' => 'NoDigitHere',
        ]))->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function register_fails_with_password_too_short(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registerPayload([
            'password'              => 'Sh0rt',
            'password_confirmation' => 'Sh0rt',
        ]))->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function register_fails_when_passwords_do_not_match(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registerPayload([
            'password_confirmation' => 'Different1',
        ]))->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function register_fails_with_special_chars_in_username(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registerPayload([
            'username' => 'john doe!',
        ]))->assertStatus(422)->assertJsonValidationErrors(['username']);
    }

    /** @test */
    public function register_fails_with_username_too_short(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registerPayload([
            'username' => 'ab',
        ]))->assertStatus(422)->assertJsonValidationErrors(['username']);
    }

    /** @test */
    public function register_cleans_up_guest_session_when_called_with_guest_token(): void
    {
        Log::spy();

        $guest = $this->makeGuestUser();
        $this->assertDatabaseHas('users', ['id' => $guest->id, 'role' => UserRole::Guest->value]);

        $plaintext = $guest->createToken('guest_token')->plainTextToken;

        $this->withToken($plaintext)
            ->postJson('/api/v1/auth/register', $this->registerPayload())
            ->assertStatus(201);

        // Guest row must be hard-deleted
        $this->assertDatabaseMissing('users', ['id' => $guest->id]);

        // Guest tokens must be revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $guest->id,
        ]);
    }

    /** @test */
    public function register_without_guest_token_does_not_affect_other_users(): void
    {
        Log::spy();
        $otherGuest = $this->makeGuestUser();

        $this->postJson('/api/v1/auth/register', $this->registerPayload())
            ->assertStatus(201);

        // The unrelated guest row must still exist
        $this->assertDatabaseHas('users', ['id' => $otherGuest->id]);
    }

    /** @test */
    public function register_fails_when_required_fields_missing(): void
    {
        $this->postJson('/api/v1/auth/register')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'username', 'password', 'full_name']);
    }
}
