<?php

namespace App\Traits;

use App\Jobs\SendNotificationJob;

/**
 * Convenience trait for any class that needs to dispatch notifications.
 * Used by services across all modules — projects, collaboration, etc.
 *
 * Usage:
 *   class ProjectApplicationService {
 *       use SendsNotifications;
 *
 *       public function accept(...) {
 *           $this->notify(
 *               userId:   $application->applicant_id,
 *               type:     'application_accepted',
 *               title:    'Your application was accepted!',
 *               body:     "You've joined {$project->title}",
 *               data:     ['project_id' => $project->id],
 *               priority: 'high',
 *           );
 *       }
 *   }
 */
trait SendsNotifications
{
    protected function notify(
        string $userId,
        string $type,
        string $title,
        string $body   = '',
        array  $data   = [],
        string $priority = 'normal',
    ): void {
        SendNotificationJob::dispatch($userId, $type, $title, $body, $data, $priority);
    }

    protected function notifyMany(
        array  $userIds,
        string $type,
        string $title,
        string $body   = '',
        array  $data   = [],
        string $priority = 'normal',
    ): void {
        foreach ($userIds as $userId) {
            $this->notify($userId, $type, $title, $body, $data, $priority);
        }
    }
}
