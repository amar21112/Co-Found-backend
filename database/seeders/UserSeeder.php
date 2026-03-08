<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSkill;
use App\Models\PortfolioItem;
use App\Models\PortfolioSkill;
use App\Models\IdentityVerification;
use App\Models\VerificationReview;
use App\Models\NotificationPreference;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin users
        User::factory()
            ->count(3)
            ->admin()
            ->active()
            ->verified()
            ->create()
            ->each(function ($user) {
                $this->createUserSkills($user, rand(5, 10));
                $this->createPortfolioItems($user, rand(2, 4));
                $this->createIdentityVerification($user, 'verified');
                NotificationPreference::factory()->create(['user_id' => $user->id]);
            });

        // Create moderators
        User::factory()
            ->count(5)
            ->moderator()
            ->active()
            ->verified()
            ->create()
            ->each(function ($user) {
                $this->createUserSkills($user, rand(4, 8));
                $this->createPortfolioItems($user, rand(1, 3));
                $this->createIdentityVerification($user, 'verified');
                NotificationPreference::factory()->create(['user_id' => $user->id]);
            });

        // Create project owners
        User::factory()
            ->count(20)
            ->projectOwner()
            ->active()
            ->create()
            ->each(function ($user) {
                $this->createUserSkills($user, rand(3, 7));
                $this->createPortfolioItems($user, rand(2, 5));
                if (rand(0, 1)) {
                    $this->createIdentityVerification($user, 'verified');
                }
                NotificationPreference::factory()->create(['user_id' => $user->id]);
            });

        // Create regular users
        User::factory()
            ->count(100)
            ->active()
            ->create()
            ->each(function ($user) {
                $this->createUserSkills($user, rand(2, 5));
                if (rand(0, 1)) {
                    $this->createPortfolioItems($user, rand(1, 3));
                }
                if (rand(0, 2) === 0) {
                    $this->createIdentityVerification($user, 'verified');
                }
                NotificationPreference::factory()->create(['user_id' => $user->id]);
            });

        // Create pending users
        User::factory()
            ->count(20)
            ->unverified()
            ->create(['account_status' => 'pending'])
            ->each(function ($user) {
                NotificationPreference::factory()->create(['user_id' => $user->id]);
            });

        // Create suspended users
        User::factory()
            ->count(5)
            ->create(['account_status' => 'suspended'])
            ->each(function ($user) {
                NotificationPreference::factory()->create(['user_id' => $user->id]);
            });

        // Create specific test users
        User::factory()->create([
            'email' => 'admin@cofound.com',
            'username' => 'admin',
            'full_name' => 'Admin User',
            'role' => 'administrator',
            'account_status' => 'active',
            'email_verified' => true,
            'identity_verified' => true
        ]);

        User::factory()->create([
            'email' => 'john.doe@example.com',
            'username' => 'johndoe',
            'full_name' => 'John Doe',
            'role' => 'project_owner',
            'account_status' => 'active',
            'email_verified' => true,
            'identity_verified' => true
        ]);

        User::factory()->create([
            'email' => 'jane.smith@example.com',
            'username' => 'janesmith',
            'full_name' => 'Jane Smith',
            'role' => 'regular_user',
            'account_status' => 'active',
            'email_verified' => true
        ]);
    }

    private function createUserSkills($user, $count)
    {
        UserSkill::factory()
            ->count($count)
            ->forUser($user->id)
            ->approved()
            ->create();
    }

    private function createPortfolioItems($user, $count)
    {
        PortfolioItem::factory()
            ->count($count)
            ->forUser($user->id)
            ->public()
            ->create()
            ->each(function ($item) {
                PortfolioSkill::factory()
                    ->count(rand(1, 3))
                    ->forPortfolioItem($item->id)
                    ->create();
            });
    }

    private function createIdentityVerification($user, $status)
    {
        $verification = IdentityVerification::factory()
            ->$status()
            ->create(['user_id' => $user->id]);

        if ($status === 'verified') {
            $user->update(['identity_verified' => true]);

            VerificationReview::factory()
                ->approved()
                ->create([
                    'verification_id' => $verification->id,
                    'reviewer_id' => User::where('role', 'moderator')->inRandomOrder()->first()?->id
                ]);
        }
    }
}
