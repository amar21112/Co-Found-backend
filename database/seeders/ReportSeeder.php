<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\User;
use App\Models\Project;
use App\Models\Message;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('account_status', 'active')->get();
        $projects = Project::all();
        $messages = Message::all();

        for ($i = 0; $i < 50; $i++) {
            $reporter = $users->random();
            $reportedUser = $users->where('id', '!=', $reporter->id)->random();
            $type = $this->getRandomReportType();

            $factory = Report::factory();

            if ($type === 'harassment' || $type === 'copyright') {
                $factory->highPriority();
            }

            $status = $this->getRandomStatus();

            if ($status === 'pending') {
                $factory->pending();
            } elseif ($status === 'under_review') {
                $factory->underReview();
            } elseif ($status === 'resolved') {
                $factory->resolved();
            }

            $report = $factory->create([
                'reporter_id' => $reporter->id,
                'reported_user_id' => $reportedUser->id,
                'report_type' => $type,
                'priority' => $this->getPriorityForType($type),
                'assigned_to' => in_array($status, ['under_review', 'resolved'])
                    ? User::where('role', 'moderator')->inRandomOrder()->first()?->id
                    : null
            ]);

            if ($type === 'spam' && $projects->count() > 0) {
                $report->update([
                    'reported_content_type' => 'project',
                    'reported_content_id' => $projects->random()->id
                ]);
            } elseif ($type === 'harassment' && $messages->count() > 0) {
                $report->update([
                    'reported_content_type' => 'message',
                    'reported_content_id' => $messages->random()->id
                ]);
            }
        }
    }

    private function getRandomReportType()
    {
        $types = ['harassment', 'spam', 'inappropriate', 'copyright', 'other'];
        return $types[array_rand($types)];
    }

    private function getRandomStatus()
    {
        $statuses = [
            'pending' => 50,
            'under_review' => 20,
            'resolved' => 20,
            'dismissed' => 7,
            'escalated' => 3
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $status;
            }
        }

        return 'pending';
    }

    private function getPriorityForType($type): string
    {
        $priorities = [
            'harassment' => 'high',
            'spam' => 'medium',
            'inappropriate' => 'medium',
            'copyright' => 'high',
            'other' => 'low'
        ];

        return $priorities[$type] ?? 'medium';
    }
}
