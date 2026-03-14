<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PortfolioItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    protected array $allSkills = [
        'PHP','Laravel','JavaScript','TypeScript','React','Vue.js','Node.js',
        'Python','Django','FastAPI','Java','Spring Boot','Go','Rust',
        'MySQL','PostgreSQL','MongoDB','Redis','Docker','Kubernetes',
        'AWS','Azure','GCP','UI/UX Design','Figma','Product Management',
        'Data Science','Machine Learning','DevOps','Blockchain','Swift','Kotlin',
    ];

    public function run(): void
    {
        // ── Fixed admin account ──────────────────────────────────────────
        $admin = User::factory()->admin()->create([
            'email'         => 'admin@cofound.io',
            'username'      => 'superadmin',
            'full_name'     => 'Super Admin',
            'password' => bcrypt('Admin@12345'),
        ]);
        $this->seedNotificationPrefs($admin->id);

        // ── Fixed moderator account ──────────────────────────────────────
        $mod = User::factory()->moderator()->create([
            'email'         => 'moderator@cofound.io',
            'username'      => 'moderator1',
            'full_name'     => 'Platform Moderator',
            'password' => bcrypt('Mod@12345'),
        ]);
        $this->seedNotificationPrefs($mod->id);

        // ── Fixed demo regular user ──────────────────────────────────────
        $demo = User::factory()->identityVerified()->create([
            'email'         => 'demo@cofound.io',
            'username'      => 'demouser',
            'full_name'     => 'Demo User',
            'password' => bcrypt('Demo@12345'),
        ]);
        $this->seedNotificationPrefs($demo->id);
        $this->seedSkillsAndPortfolio($demo);

        // ── Random regular users ─────────────────────────────────────────
        $users = User::factory(40)->create();
        foreach ($users as $user) {
            $this->seedNotificationPrefs($user->id);
            $this->seedSkillsAndPortfolio($user);
        }

        // ── Unverified / pending users ───────────────────────────────────
        User::factory(10)->unverified()->create()->each(
            fn($u) => $this->seedNotificationPrefs($u->id)
        );

        $this->command->info('UserSeeder: admins, moderators, and 50+ users created.');
    }

    private function seedNotificationPrefs(string $userId): void
    {
        \DB::table('notification_preferences')->insertOrIgnore([
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

        // The (user_id, skill_name) unique index is respected; insertOrIgnore
        // silently skips any accidental duplicate within the same batch.
        \DB::table('user_skills')->insertOrIgnore($rows);

        PortfolioItem::factory(rand(1, 3))->create(['user_id' => $user->id]);
    }
}
