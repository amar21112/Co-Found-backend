<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Report;
use App\Models\AdminAction;
use App\Models\UserRestriction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdministrationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSystemSettings();
        $this->seedReports();
        $this->seedAdminActions();
        $this->seedUserRestrictions();

        $this->command->info('AdministrationSeeder: system settings, reports, admin actions, and restrictions seeded.');
    }

    private function seedSystemSettings(): void
    {
        $settings = [
            [
                'setting_key'   => 'platform.maintenance_mode',
                'setting_value' => json_encode(false),
                'setting_type'  => 'boolean',
                'description'   => 'Toggle platform maintenance mode',
                'is_public'     => false,
            ],
            [
                'setting_key'   => 'platform.max_team_size',
                'setting_value' => json_encode(20),
                'setting_type'  => 'integer',
                'description'   => 'Maximum allowed team size per project',
                'is_public'     => true,
            ],
            [
                'setting_key'   => 'platform.application_limit_per_user',
                'setting_value' => json_encode(10),
                'setting_type'  => 'integer',
                'description'   => 'Max open applications a single user can have',
                'is_public'     => false,
            ],
            [
                'setting_key'   => 'platform.identity_verification_required',
                'setting_value' => json_encode(false),
                'setting_type'  => 'boolean',
                'description'   => 'Require identity verification before applying to projects',
                'is_public'     => true,
            ],
            [
                'setting_key'   => 'notifications.email_digest_interval',
                'setting_value' => json_encode('daily'),
                'setting_type'  => 'string',
                'description'   => 'Default email digest interval for new users',
                'is_public'     => false,
            ],
            [
                'setting_key'   => 'matching.minimum_compatibility_score',
                'setting_value' => json_encode(0.50),
                'setting_type'  => 'float',
                'description'   => 'Minimum score to surface a match to users',
                'is_public'     => false,
            ],
            [
                'setting_key'   => 'matching.match_expiry_days',
                'setting_value' => json_encode(30),
                'setting_type'  => 'integer',
                'description'   => 'Days before an unactioned match expires',
                'is_public'     => false,
            ],
            [
                'setting_key'   => 'files.max_upload_size_mb',
                'setting_value' => json_encode(50),
                'setting_type'  => 'integer',
                'description'   => 'Maximum file upload size in MB',
                'is_public'     => true,
            ],
            [
                'setting_key'   => 'platform.allowed_id_types',
                'setting_value' => json_encode(['passport', 'drivers_license', 'national_id']),
                'setting_type'  => 'array',
                'description'   => 'Accepted ID types for identity verification',
                'is_public'     => true,
            ],
            [
                'setting_key'   => 'platform.registration_open',
                'setting_value' => json_encode(true),
                'setting_type'  => 'boolean',
                'description'   => 'Whether new user registration is currently open',
                'is_public'     => true,
            ],
        ];

        $adminId = User::where('role', 'admin')->value('id');

        foreach ($settings as $setting) {
            \DB::table('system_settings')->insertOrIgnore(array_merge($setting, [
                'id'         => Str::uuid(),
                'updated_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    private function seedReports(): void
    {
        // Pending reports (unassigned)
        Report::factory(10)->pending()->create();

        // Under review
        Report::factory(5)->create(['status' => 'under_review']);

        // Resolved
        Report::factory(10)->create(['status' => 'resolved']);

        // Dismissed
        Report::factory(5)->create(['status' => 'dismissed']);
    }

    private function seedAdminActions(): void
    {
        AdminAction::factory(20)->create();
    }

    private function seedUserRestrictions(): void
    {
        $users = User::where('role', 'regular_user')->inRandomOrder()->take(8)->get();

        foreach ($users as $index => $user) {
            if ($index < 3) {
                // Active restrictions
                UserRestriction::factory()->create([
                    'user_id'          => $user->id,
                    'restriction_type' => 'messaging_ban',
                    'is_active'        => true,
                    'expires_at'       => now()->addDays(rand(1, 7)),
                ]);
            } elseif ($index < 6) {
                // Expired restrictions
                UserRestriction::factory()->create([
                    'user_id'    => $user->id,
                    'is_active'  => false,
                    'expires_at' => now()->subDays(rand(1, 14)),
                ]);
            } else {
                // Permanent (no expiry) — still active
                UserRestriction::factory()->create([
                    'user_id'        => $user->id,
                    'is_active'      => true,
                    'duration_hours' => null,
                    'expires_at'     => null,
                ]);
            }
        }
    }
}
