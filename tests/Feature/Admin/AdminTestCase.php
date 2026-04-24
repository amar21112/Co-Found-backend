<?php

namespace Tests\Feature\Admin;

use App\Models\IdentityVerification;
use App\Models\User;
use App\Models\UserRestriction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    // ── User helpers ──────────────────────────────────────────────────────────

    protected function makeAdmin(array $overrides = []): User
    {
        return User::factory()->admin()->create(array_merge([
            'password' => Hash::make('Secret123'),
        ], $overrides));
    }

    protected function makeModerator(array $overrides = []): User
    {
        return User::factory()->moderator()->create(array_merge([
            'password' => Hash::make('Secret123'),
        ], $overrides));
    }

    protected function makeRegularUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'password' => Hash::make('Secret123'),
        ], $overrides));
    }

    // ── Verification helpers ──────────────────────────────────────────────────

    protected function makePendingVerification(array $overrides = []): IdentityVerification
    {
        return IdentityVerification::factory()->create(array_merge([
            'verification_status' => 'pending',
        ], $overrides));
    }

    protected function makeVerifiedVerification(array $overrides = []): IdentityVerification
    {
        return IdentityVerification::factory()->verified()->create($overrides);
    }

    protected function makeRejectedVerification(array $overrides = []): IdentityVerification
    {
        return IdentityVerification::factory()->rejected()->create($overrides);
    }

    // ── Restriction helpers ───────────────────────────────────────────────────

    protected function makeActiveRestriction(User $target, User $admin, string $type = 'messaging_ban'): UserRestriction
    {
        return UserRestriction::factory()->create([
            'user_id'          => $target->id,
            'restricted_by'    => $admin->id,
            'restriction_type' => $type,
            'is_active'        => true,
            'expires_at'       => now()->addDay(),
        ]);
    }

    protected function makeLiftedRestriction(User $target, User $admin): UserRestriction
    {
        return UserRestriction::factory()->create([
            'user_id'          => $target->id,
            'restricted_by'    => $admin->id,
            'restriction_type' => 'messaging_ban',
            'is_active'        => false,
            'lifted_by'        => $admin->id,
            'lifted_at'        => now()->subHour(),
        ]);
    }
}
