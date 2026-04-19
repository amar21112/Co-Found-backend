<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\IdentityVerificationLevel;
use App\Enums\UserRole;
use App\Models\PortfolioItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    // ── Skill pool ────────────────────────────────────────────────────────────
    protected array $allSkills = [
        'PHP', 'Laravel', 'JavaScript', 'TypeScript', 'React', 'Vue.js', 'Node.js',
        'Python', 'Django', 'FastAPI', 'Java', 'Spring Boot', 'Go', 'Rust',
        'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Docker', 'Kubernetes',
        'AWS', 'Azure', 'GCP', 'UI/UX Design', 'Figma', 'Product Management',
        'Data Science', 'Machine Learning', 'DevOps', 'Blockchain', 'Swift', 'Kotlin',
    ];

    // ── Fixed team accounts (predictable credentials for development) ─────────
    // These are the accounts the dev team uses while building and testing.
    // Password pattern: <Role>@12345  (e.g. Admin@12345)
    private array $teamMembers = [
        // ── Admins ───────────────────────────────────────────────────────────
        [
            'email'     => 'admin@cofound.io',
            'username'  => 'superadmin',
            'full_name' => 'Super Admin',
            'password'  => 'Admin@12345',
            'role'      => 'admin',
            'note'      => 'Full platform access',
        ],

        // ── Moderators ────────────────────────────────────────────────────────
        [
            'email'     => 'moderator@cofound.io',
            'username'  => 'moderator1',
            'full_name' => 'Platform Moderator',
            'password'  => 'Mod@12345',
            'role'      => 'moderator',
            'note'      => 'Content moderation access',
        ],

        // ── Active verified regular users ─────────────────────────────────────
        [
            'email'     => 'alice@cofound.io',
            'username'  => 'alice_dev',
            'full_name' => 'Alice Johnson',
            'password'  => 'Alice@12345',
            'role'      => 'regular',
            'note'      => 'Active verified user — email + identity verified',
            'identity'  => true,
        ],
        [
            'email'     => 'bob@cofound.io',
            'username'  => 'bob_founder',
            'full_name' => 'Bob Martinez',
            'password'  => 'Bob@12345',
            'role'      => 'regular',
            'note'      => 'Active verified user — email verified only',
            'identity'  => false,
        ],
        [
            'email'     => 'carol@cofound.io',
            'username'  => 'carol_pm',
            'full_name' => 'Carol Smith',
            'password'  => 'Carol@12345',
            'role'      => 'regular',
            'note'      => 'Active verified user — for collaboration flow testing',
            'identity'  => false,
        ],
        [
            'email'     => 'dave@cofound.io',
            'username'  => 'dave_backend',
            'full_name' => 'Dave Williams',
            'password'  => 'Dave@12345',
            'role'      => 'regular',
            'note'      => 'Active verified user — for project/application testing',
            'identity'  => false,
        ],

        // ── Pending user (email not verified) ────────────────────────────────
        [
            'email'     => 'pending@cofound.io',
            'username'  => 'pending_user',
            'full_name' => 'Pending User',
            'password'  => 'Pending@12345',
            'role'      => 'pending',
            'note'      => 'Registered but email not verified — tests soft-block behaviour',
        ],

        // ── Suspended user ────────────────────────────────────────────────────
        [
            'email'     => 'suspended@cofound.io',
            'username'  => 'suspended_user',
            'full_name' => 'Suspended User',
            'password'  => 'Suspended@12345',
            'role'      => 'suspended',
            'note'      => 'Suspended account — login returns 403',
        ],

        // ── Guest (ephemeral) — just for reference ────────────────────────────
        // Real guest accounts are created via POST /auth/guest at runtime;
        // this one is a static reference row for manual API testing.
        [
            'email'     => 'guest@cofound.io',
            'username'  => 'demo_guest',
            'full_name' => 'Demo Guest',
            'password'  => 'Guest@12345',
            'role'      => 'guest',
            'note'      => 'Static guest row — read-only browsing, blocked on write routes',
        ],

        // ── Demo user (generic, for quick demos) ──────────────────────────────
        [
            'email'     => 'demo@cofound.io',
            'username'  => 'demouser',
            'full_name' => 'Demo User',
            'password'  => 'Demo@12345',
            'role'      => 'regular',
            'note'      => 'Generic demo account — identity + email verified',
            'identity'  => true,
        ],
    ];

    public function run(): void
    {
        // ── 1. Fixed team accounts ────────────────────────────────────────────
        foreach ($this->teamMembers as $member) {
            $user = $this->createTeamMember($member);

            $this->seedNotificationPrefs($user->id);

            // Give regular + demo users skills & portfolio so profile responses
            // are populated during frontend / API testing
            if (in_array($member['role'], ['regular'])) {
                $this->seedSkillsAndPortfolio($user);
            }
        }

        // ── 2. Random active regular users ────────────────────────────────────
        $regulars = User::factory(40)->create();
        foreach ($regulars as $user) {
            $this->seedNotificationPrefs($user->id);
            $this->seedSkillsAndPortfolio($user);
        }

        // ── 3. Pending / unverified users ─────────────────────────────────────
        User::factory(10)->unverified()->create()->each(
            fn($u) => $this->seedNotificationPrefs($u->id)
        );

        // ── 4. Summary ────────────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('UserSeeder: team accounts + 50+ random users created.');
        $this->command->info('');
        $this->command->info('  ┌────────────────────────────────────────────────────────────┐');
        $this->command->info('  │              Co-Found — Dev Team Accounts                   │');
        $this->command->info('  ├───────────────────────┬──────────────────┬─────────────────┤');
        $this->command->info('  │ Email                 │ Password         │ Role / State    │');
        $this->command->info('  ├───────────────────────┼──────────────────┼─────────────────┤');
        $this->command->info('  │ admin@cofound.io      │ Admin@12345      │ administrator   │');
        $this->command->info('  │ moderator@cofound.io  │ Mod@12345        │ moderator       │');
        $this->command->info('  │ alice@cofound.io      │ Alice@12345      │ regular (full)  │');
        $this->command->info('  │ bob@cofound.io        │ Bob@12345        │ regular         │');
        $this->command->info('  │ carol@cofound.io      │ Carol@12345      │ regular         │');
        $this->command->info('  │ dave@cofound.io       │ Dave@12345       │ regular         │');
        $this->command->info('  │ demo@cofound.io       │ Demo@12345       │ regular (full)  │');
        $this->command->info('  │ pending@cofound.io    │ Pending@12345    │ pending ⚠       │');
        $this->command->info('  │ suspended@cofound.io  │ Suspended@12345  │ suspended 🚫    │');
        $this->command->info('  │ guest@cofound.io      │ Guest@12345      │ guest (static)  │');
        $this->command->info('  └───────────────────────┴──────────────────┴─────────────────┘');
        $this->command->info('');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createTeamMember(array $member): User
    {
        $base = [
            'email'    => $member['email'],
            'username' => $member['username'],
            'full_name'=> $member['full_name'],
            'password' => bcrypt($member['password']),
        ];

        return match($member['role']) {
            'admin' => User::factory()->admin()->create($base),

            'moderator' => User::factory()->moderator()->create($base),

            'regular' => ($member['identity'] ?? false)
                ? User::factory()->fullyVerified()->create($base)
                : User::factory()->create($base),

            'pending' => User::factory()->unverified()->create($base),

            'suspended' => User::factory()->suspended()->create($base),

            'guest' => User::factory()->guest()->create(array_merge($base, [
                'email'    => $member['email'],   // override the auto-generated guest email
                'username' => $member['username'],
            ])),

            default => User::factory()->create($base),
        };
    }

    private function seedNotificationPrefs(string $userId): void
    {
        DB::table('notification_preferences')->insertOrIgnore([
            'id'                     => Str::uuid(),
            'user_id'                => $userId,
            'platform_notifications' => true,
            'email_notifications'    => true,
            'push_notifications'     => true,
            'notification_digest'    => 'immediate',
            'preferences'            => json_encode([]),
            'updated_at'             => now(),
        ]);
    }

    private function seedSkillsAndPortfolio(User $user): void
    {
        // Shuffle a fresh copy per user — uniqueness is scoped per-user,
        // never via Faker's global unique() which exhausts after ~32 calls.
        $picked = collect($this->allSkills)->shuffle()->take(rand(3, 7));

        $rows = $picked->map(fn($skill) => [
            'id'                => (string) Str::uuid(),
            'user_id'           => $user->id,
            'skill_name'        => $skill,
            'proficiency_level' => rand(1, 5),
            'years_experience'  => round(rand(5, 150) / 10, 1),
            'is_approved'       => (bool) rand(0, 1),
            'created_at'        => now(),
            'updated_at'        => now(),
        ])->values()->all();

        DB::table('user_skills')->insertOrIgnore($rows);

        PortfolioItem::factory(rand(1, 3))->create(['user_id' => $user->id]);
    }
}
