<?php

namespace App\Services\Admin;

use App\Models\AdminAction;
use App\Models\User;

/**
 * Logs every admin/moderator action to the admin_actions table.
 *
 * Every mutating admin operation (review, restrict, lift) must call
 * log() so there is a full audit trail of who did what and when.
 */
class AdminActionLogger
{
    public function log(
        User    $admin,
        string  $actionType,
        string  $targetType,
        string  $targetId,
        array   $details = [],
        ?string $ip = null,
    ): AdminAction {
        return AdminAction::create([
            'admin_id'    => $admin->id,
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'details'     => $details,
            'ip_address'  => $ip,
        ]);
    }
}
