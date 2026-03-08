<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Authentication Module
            UserSeeder::class,
            PasswordResetSeeder::class,
            SessionSeeder::class,
            IdentityVerificationSeeder::class,
            VerificationReviewSeeder::class,
            VerificationAttemptSeeder::class,
            UserSkillSeeder::class,
            SkillEndorsementSeeder::class,
            PortfolioItemSeeder::class,
            PortfolioSkillSeeder::class,

            // Project Management Module
            ProjectSeeder::class,
            ProjectSkillSeeder::class,
            ProjectRoleSeeder::class,
            ProjectMilestoneSeeder::class,
            ProjectTeamMemberSeeder::class,
            ProjectApplicationSeeder::class,
            ApplicationSkillSeeder::class,

            // Collaboration Module
            UserConnectionSeeder::class,
            CollaborationInvitationSeeder::class,
            MatchSeeder::class,
            MatchFeedbackSeeder::class,
            CollaborationRatingSeeder::class,

            // Communication Module
            ConversationSeeder::class,
            ConversationParticipantSeeder::class,
            MessageSeeder::class,
            MessageReadReceiptSeeder::class,
            MessageReactionSeeder::class,
            FileSeeder::class,
            SharedFileSeeder::class,
            VideoCallSeeder::class,
            CallParticipantSeeder::class,
            NotificationSeeder::class,
            NotificationPreferenceSeeder::class,

            // Administration Module
            AdminActionSeeder::class,
            ReportSeeder::class,
            ContentModerationSeeder::class,
            UserRestrictionSeeder::class,
            SystemLogSeeder::class,
            AnalyticsEventSeeder::class,
            SystemSettingSeeder::class,
            ConfigurationHistorySeeder::class,
        ]);
    }
}
