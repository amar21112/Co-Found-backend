<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MissingTablesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedApplicationSkills();
        $this->seedPortfolioSkills();
        $this->seedSkillEndorsements();
        $this->seedMatchFeedback();
        $this->seedMessageReadReceipts();
        $this->seedMessageReactions();
        $this->seedContentModeration();
        $this->seedConfigurationHistory();

        $this->command->info('MissingTablesSeeder: all 8 tables populated.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // application_skills
    // Each project application gets 2–5 skill claims that mirror what the
    // project itself requires, so the data looks realistic.
    // ─────────────────────────────────────────────────────────────────────────
    private function seedApplicationSkills(): void
    {
        $applications = \DB::table('project_applications')->get();

        if ($applications->isEmpty()) {
            $this->command->warn('  application_skills: no applications found, skipping.');
            return;
        }

        $rows = [];

        foreach ($applications as $application) {
            // Pull the skills required by the project this person applied to
            $projectSkills = \DB::table('project_skills')
                ->where('project_id', $application->project_id)
                ->pluck('skill_name')
                ->shuffle()
                ->take(rand(2, 5));

            foreach ($projectSkills as $skill) {
                $rows[] = [
                    'id'                 => (string) Str::uuid(),
                    'application_id'     => $application->id,
                    'skill_name'         => $skill,
                    'proficiency_claimed' => rand(2, 5),
                ];
            }
        }

        // Chunk to avoid hitting MySQL's max_allowed_packet on large datasets
        foreach (array_chunk($rows, 200) as $chunk) {
            \DB::table('application_skills')->insertOrIgnore($chunk);
        }

        $this->command->info('  application_skills: ' . count($rows) . ' rows inserted.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // portfolio_skills
    // Tag each portfolio item with 1–4 skills drawn from the owner's skill set.
    // ─────────────────────────────────────────────────────────────────────────
    private function seedPortfolioSkills(): void
    {
        $items = \DB::table('portfolio_items')->get();

        if ($items->isEmpty()) {
            $this->command->warn('  portfolio_skills: no portfolio items found, skipping.');
            return;
        }

        $rows = [];

        foreach ($items as $item) {
            $ownerSkills = \DB::table('user_skills')
                ->where('user_id', $item->user_id)
                ->pluck('skill_name')
                ->shuffle()
                ->take(rand(1, 4));

            // Fallback: pick random generic skills if the user has none yet
            if ($ownerSkills->isEmpty()) {
                $ownerSkills = collect(['JavaScript', 'React', 'UI/UX Design', 'Python'])
                    ->shuffle()->take(rand(1, 2));
            }

            foreach ($ownerSkills as $skill) {
                $rows[] = [
                    'id'                => (string) Str::uuid(),
                    'portfolio_item_id' => $item->id,
                    'skill_name'        => $skill,
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            \DB::table('portfolio_skills')->insertOrIgnore($chunk);
        }

        $this->command->info('  portfolio_skills: ' . count($rows) . ' rows inserted.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // skill_endorsements
    // Connected users endorse each other's skills (1–3 endorsements per skill).
    // ─────────────────────────────────────────────────────────────────────────
    private function seedSkillEndorsements(): void
    {
        // Only accepted connections can endorse
        $connections = \DB::table('user_connections')
            ->where('status', 'accepted')
            ->get();

        if ($connections->isEmpty()) {
            $this->command->warn('  skill_endorsements: no accepted connections found, skipping.');
            return;
        }

        $rows  = [];
        $pairs = collect(); // track (user_skill_id, endorser_id) to avoid dupes

        foreach ($connections as $conn) {
            // Endorser → skills of the recipient
            foreach ([$conn->requester_id => $conn->recipient_id, $conn->recipient_id => $conn->requester_id] as $endorserId => $skillOwnerId) {
                $skills = \DB::table('user_skills')
                    ->where('user_id', $skillOwnerId)
                    ->inRandomOrder()
                    ->take(rand(1, 3))
                    ->get(['id']);

                foreach ($skills as $skill) {
                    $key = $skill->id . '-' . $endorserId;
                    if ($pairs->contains($key)) continue;
                    $pairs->push($key);

                    $rows[] = [
                        'id'                  => (string) Str::uuid(),
                        'user_skill_id'       => $skill->id,
                        'endorsed_by_user_id' => $endorserId,
                        'created_at'          => now()->subDays(rand(1, 90)),
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            \DB::table('skill_endorsements')->insertOrIgnore($chunk);
        }

        $this->command->info('  skill_endorsements: ' . count($rows) . ' rows inserted.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // match_feedback
    // Users react to their matches — thumbs up / down / skip.
    // ─────────────────────────────────────────────────────────────────────────
    private function seedMatchFeedback(): void
    {
        // Only matches that were viewed can have feedback
        $matches = \DB::table('matches')->where('viewed', true)->get();

        if ($matches->isEmpty()) {
            $this->command->warn('  match_feedback: no viewed matches found, skipping.');
            return;
        }

        $rows = [];

        foreach ($matches as $match) {
            // ~60 % of viewed matches get feedback
            if (rand(0, 9) >= 6) continue;

            $rows[] = [
                'id'            => (string) Str::uuid(),
                'match_id'      => $match->id,
                'user_id'       => $match->user_id,
                'feedback_type' => fake()->randomElement(['interested', 'not_interested', 'saved', 'skipped']),
                'created_at'    => now()->subDays(rand(1, 30)),
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            \DB::table('match_feedback')->insertOrIgnore($chunk);
        }

        $this->command->info('  match_feedback: ' . count($rows) . ' rows inserted.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // message_read_receipts
    // Mark messages as read for participants other than the sender.
    // ─────────────────────────────────────────────────────────────────────────
    private function seedMessageReadReceipts(): void
    {
        // Grab a sample of messages (not deleted) with their conversations
        $messages = \DB::table('messages')
            ->whereNull('deleted_at')
            ->inRandomOrder()
            ->take(300)
            ->get(['id', 'conversation_id', 'sender_id', 'created_at']);

        if ($messages->isEmpty()) {
            $this->command->warn('  message_read_receipts: no messages found, skipping.');
            return;
        }

        $rows = [];

        foreach ($messages as $message) {
            // Get all participants of this conversation except the sender
            $readers = \DB::table('conversation_participants')
                ->where('conversation_id', $message->conversation_id)
                ->where('user_id', '!=', $message->sender_id)
                ->pluck('user_id');

            foreach ($readers as $readerId) {
                // ~75 % chance the message was actually read
                if (rand(0, 3) === 0) continue;

                $rows[] = [
                    'id'         => (string) Str::uuid(),
                    'message_id' => $message->id,
                    'user_id'    => $readerId,
                    'read_at'    => now()->subMinutes(rand(1, 2880)),
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            \DB::table('message_read_receipts')->insertOrIgnore($chunk);
        }

        $this->command->info('  message_read_receipts: ' . count($rows) . ' rows inserted.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // message_reactions
    // Emoji reactions on messages — 1–3 reactions per sampled message.
    // ─────────────────────────────────────────────────────────────────────────
    private function seedMessageReactions(): void
    {
        $emojis = ['👍', '❤️', '😂', '🎉', '🔥', '👏', '😮', '🤔', '💯', '🚀'];

        $messages = \DB::table('messages')
            ->whereNull('deleted_at')
            ->inRandomOrder()
            ->take(150)
            ->get(['id', 'conversation_id', 'sender_id']);

        if ($messages->isEmpty()) {
            $this->command->warn('  message_reactions: no messages found, skipping.');
            return;
        }

        $rows  = [];
        $pairs = collect(); // (message_id, user_id, reaction) must be unique

        foreach ($messages as $message) {
            $participants = \DB::table('conversation_participants')
                ->where('conversation_id', $message->conversation_id)
                ->pluck('user_id')
                ->shuffle()
                ->take(rand(1, 3));

            foreach ($participants as $userId) {
                $emoji = fake()->randomElement($emojis);
                $key   = $message->id . '-' . $userId . '-' . $emoji;
                if ($pairs->contains($key)) continue;
                $pairs->push($key);

                $rows[] = [
                    'id'         => (string) Str::uuid(),
                    'message_id' => $message->id,
                    'user_id'    => $userId,
                    'reaction'   => $emoji,
                    'created_at' => now()->subMinutes(rand(1, 1440)),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            \DB::table('message_reactions')->insertOrIgnore($chunk);
        }

        $this->command->info('  message_reactions: ' . count($rows) . ' rows inserted.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // content_moderation
    // Moderators taking action on reported messages, projects, and profiles.
    // ─────────────────────────────────────────────────────────────────────────
    private function seedContentModeration(): void
    {
        $moderators = \DB::table('users')
            ->whereIn('role', ['moderator', 'admin'])
            ->pluck('id');

        if ($moderators->isEmpty()) {
            $this->command->warn('  content_moderation: no moderators found, skipping.');
            return;
        }

        $contentTypes = [
            'message' => \DB::table('messages')->whereNull('deleted_at')->inRandomOrder()->take(10)->pluck('id'),
            'project' => \DB::table('projects')->inRandomOrder()->take(8)->pluck('id'),
            'profile' => \DB::table('users')->where('role', 'regular_user')->inRandomOrder()->take(7)->pluck('id'),
        ];

        $actions = ['approved', 'edited', 'removed', 'quarantined', 'escalated'];

        $rows = [];

        foreach ($contentTypes as $type => $ids) {
            foreach ($ids as $contentId) {
                // ~50 % of items get a moderation record
                if (rand(0, 1) === 0) continue;

                $action = fake()->randomElement($actions);

                $rows[] = [
                    'id'                   => (string) Str::uuid(),
                    'moderator_id'         => $moderators->random(),
                    'content_type'         => $type,
                    'content_id'           => $contentId,
                    'moderation_type'      => fake()->randomElement(['reported', 'auto_flagged', 'random_sampling', 'targeted']),
                    'original_content'     => $action !== 'no_action' ? fake()->paragraph() : null,
                    'moderated_content'    => $action === 'content_edited' ? fake()->paragraph() : null,
                    'action_taken'         => $action,
                    'reason'               => fake()->sentence(),
                    'guideline_referenced' => fake()->randomElement([
                        'community_guidelines_3.1',
                        'terms_of_service_5.2',
                        'harassment_policy',
                        'spam_policy',
                        null,
                    ]),
                    'created_at' => now()->subDays(rand(1, 60)),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            \DB::table('content_moderation')->insert($chunk);
        }

        $this->command->info('  content_moderation: ' . count($rows) . ' rows inserted.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // configuration_history
    // Audit trail of changes made to system_settings.
    // ─────────────────────────────────────────────────────────────────────────
    private function seedConfigurationHistory(): void
    {
        $settings = \DB::table('system_settings')->get(['setting_key', 'setting_value', 'updated_by']);

        if ($settings->isEmpty()) {
            $this->command->warn('  configuration_history: no system settings found, skipping.');
            return;
        }

        $admins = \DB::table('users')
            ->whereIn('role', ['admin'])
            ->pluck('id');

        if ($admins->isEmpty()) {
            $this->command->warn('  configuration_history: no admin users found, skipping.');
            return;
        }

        $rows = [];

        // Simulate 1–3 historical changes per setting
        foreach ($settings as $setting) {
            $changeCount = rand(1, 3);

            for ($i = 0; $i < $changeCount; $i++) {
                // Generate a plausible "old" value based on the current type
                $currentValue = json_decode($setting->setting_value, true);
                $oldValue     = $this->mutateValue($currentValue);

                $rows[] = [
                    'id'            => (string) Str::uuid(),
                    'setting_key'   => $setting->setting_key,
                    'old_value'     => json_encode($oldValue),
                    'new_value'     => $i === $changeCount - 1 ? $setting->setting_value : json_encode($this->mutateValue($currentValue)),
                    'changed_by'    => $admins->random(),
                    'change_reason' => fake()->randomElement([
                        'Routine platform update',
                        'Post-launch adjustment',
                        'User feedback response',
                        'Security policy change',
                        'Performance optimisation',
                        null,
                    ]),
                    'created_at' => now()->subDays(rand(10, 180)),
                ];
            }
        }

        // Sort by created_at so the history reads chronologically
        usort($rows, fn($a, $b) => $a['created_at'] <=> $b['created_at']);

        foreach (array_chunk($rows, 100) as $chunk) {
            \DB::table('configuration_history')->insert($chunk);
        }

        $this->command->info('  configuration_history: ' . count($rows) . ' rows inserted.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: produce a slightly different value for config history "old" values
    // ─────────────────────────────────────────────────────────────────────────
    private function mutateValue(mixed $value): mixed
    {
        if (is_bool($value))    return !$value;
        if (is_int($value))     return max(1, $value + fake()->randomElement([-5, -2, -1, 1, 2, 5]));
        if (is_float($value))   return round(max(0.1, $value + fake()->randomElement([-0.1, -0.05, 0.05, 0.1])), 2);
        if (is_string($value))  return fake()->randomElement(['immediate', 'daily', 'weekly', 'never']);
        if (is_array($value))   return array_slice($value, 0, max(1, count($value) - 1));
        return $value;
    }
}
