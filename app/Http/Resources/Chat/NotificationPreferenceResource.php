<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'platform_notifications' => $this->platform_notifications,
            'email_notifications'    => $this->email_notifications,
            'push_notifications'     => $this->push_notifications,
            'notification_digest'    => $this->notification_digest,
            'quiet_hours_start'      => $this->quiet_hours_start,
            'quiet_hours_end'        => $this->quiet_hours_end,
            'quiet_hours_timezone'   => $this->quiet_hours_timezone,
            'preferences'            => $this->preferences ?? [],
            'updated_at'             => $this->updated_at?->toISOString(),
        ];
    }
}
