<?php

namespace App\Services\Admin;

use App\DTOs\Admin\LogModerationActionDTO;
use App\Models\ContentModeration;
use App\Models\User;
use App\Repositories\Contracts\AdminModerationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class AdminModerationService
{
    public function __construct(
        private AdminModerationRepositoryInterface $moderationRepo,
        private AdminActionLogger                  $logger,
    ) {}

    // =========================================================================
    // List
    // =========================================================================

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->moderationRepo->paginate($filters, $perPage);
    }

    // =========================================================================
    // Log action
    // =========================================================================

    /**
     * Create a moderation log entry and record it in admin_actions.
     *
     * The ContentModeration table is the granular content-level log.
     * admin_actions records that a moderator performed this act.
     */
    public function log(
        LogModerationActionDTO $dto,
        User                   $admin,
        string                 $ip,
    ): ContentModeration {
        $moderation = $this->moderationRepo->create([
            'moderator_id'        => $dto->moderatorId,
            'content_type'        => $dto->contentType,
            'content_id'          => $dto->contentId,
            'moderation_type'     => $dto->moderationType,
            'original_content'    => $dto->originalContent,
            'moderated_content'   => $dto->moderatedContent,
            'action_taken'        => $dto->actionTaken,
            'reason'              => $dto->reason,
            'guideline_referenced'=> $dto->guidelineReferenced,
        ]);

        $this->logger->log(
            admin:      $admin,
            actionType: 'moderation_action_logged',
            targetType: $dto->contentType,
            targetId:   $dto->contentId,
            details:    [
                'moderation_id'   => $moderation->id,
                'action_taken'    => $dto->actionTaken,
                'moderation_type' => $dto->moderationType,
                'reason'          => $dto->reason,
            ],
            ip: $ip,
        );

        return $moderation->load('moderator');
    }
}
