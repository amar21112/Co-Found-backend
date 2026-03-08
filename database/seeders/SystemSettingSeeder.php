<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'administrator')->first();

        $settings = [
            [
                'key' => 'site_name',
                'value' => 'Co-Found Platform',
                'type' => 'string',
                'description' => 'The name of the platform',
                'is_public' => true
            ],
            [
                'key' => 'site_description',
                'value' => 'Collaboration Platform for Developers',
                'type' => 'string',
                'description' => 'Site meta description',
                'is_public' => true
            ],
            [
                'key' => 'maintenance_mode',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Enable maintenance mode',
                'is_public' => false
            ],
            [
                'key' => 'registration_enabled',
                'value' => true,
                'type' => 'boolean',
                'description' => 'Allow new user registrations',
                'is_public' => true
            ],
            [
                'key' => 'email_verification_required',
                'value' => true,
                'type' => 'boolean',
                'description' => 'Require email verification',
                'is_public' => true
            ],
            [
                'key' => 'identity_verification_required',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Require ID verification',
                'is_public' => true
            ],
            [
                'key' => 'max_file_size_mb',
                'value' => 100,
                'type' => 'integer',
                'description' => 'Maximum file upload size in MB',
                'is_public' => true
            ],
            [
                'key' => 'allowed_file_types',
                'value' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip'],
                'type' => 'array',
                'description' => 'Allowed file extensions',
                'is_public' => true
            ],
            [
                'key' => 'default_user_role',
                'value' => 'regular_user',
                'type' => 'string',
                'description' => 'Default role for new users',
                'is_public' => false
            ],
            [
                'key' => 'session_timeout_minutes',
                'value' => 120,
                'type' => 'integer',
                'description' => 'Session lifetime in minutes',
                'is_public' => false
            ],
            [
                'key' => 'max_login_attempts',
                'value' => 5,
                'type' => 'integer',
                'description' => 'Maximum login attempts before lockout',
                'is_public' => false
            ],
            [
                'key' => 'lockout_duration_minutes',
                'value' => 30,
                'type' => 'integer',
                'description' => 'Lockout duration after failed attempts',
                'is_public' => false
            ],
            [
                'key' => 'project_creation_limit',
                'value' => 10,
                'type' => 'integer',
                'description' => 'Maximum projects per user',
                'is_public' => false
            ],
            [
                'key' => 'team_size_limit',
                'value' => 20,
                'type' => 'integer',
                'description' => 'Maximum team size per project',
                'is_public' => true
            ],
            [
                'key' => 'matching_algorithm_version',
                'value' => 'v2.1',
                'type' => 'string',
                'description' => 'Current matching algorithm version',
                'is_public' => false
            ],
            [
                'key' => 'default_notification_digest',
                'value' => 'daily',
                'type' => 'string',
                'description' => 'Default notification frequency',
                'is_public' => true
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'The platform is currently under maintenance. We\'ll be back soon!',
                'type' => 'string',
                'description' => 'Message to display during maintenance',
                'is_public' => true
            ],
            [
                'key' => 'support_email',
                'value' => 'support@cofound.com',
                'type' => 'string',
                'description' => 'Support email address',
                'is_public' => true
            ],
            [
                'key' => 'privacy_policy_url',
                'value' => '/privacy',
                'type' => 'string',
                'description' => 'Privacy policy URL',
                'is_public' => true
            ],
            [
                'key' => 'terms_of_service_url',
                'value' => '/terms',
                'type' => 'string',
                'description' => 'Terms of service URL',
                'is_public' => true
            ]
        ];

        foreach ($settings as $setting) {
            $factory = SystemSetting::factory();

            if ($setting['type'] === 'string') {
                $factory->string();
            } elseif ($setting['type'] === 'boolean') {
                $factory->boolean();
            } elseif ($setting['type'] === 'integer') {
                $factory->integer();
            } elseif ($setting['type'] === 'array') {
                $factory->array();
            }

            if ($setting['is_public']) {
                $factory->public();
            } else {
                $factory->private();
            }

            $factory->create([
                'setting_key' => $setting['key'],
                'setting_value' => $setting['value'],
                'setting_type' => $setting['type'],
                'description' => $setting['description'],
                'is_public' => $setting['is_public'],
                'updated_by' => $admin?->id
            ]);
        }
    }
}
