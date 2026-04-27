<?php

namespace App\Http\Resources\Report;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'report_type'           => $this->report_type,
            'description'           => $this->description,
            'evidence'              => $this->evidence,
            'status'                => $this->status,
            'priority'              => $this->priority,
            'reported_content_type' => $this->reported_content_type,
            'reported_content_id'   => $this->reported_content_id,
            'resolution_action'     => $this->when($this->status === 'resolved', $this->resolution_action),
            'resolution_notes'      => $this->when(in_array($this->status, ['resolved', 'dismissed']), $this->resolution_notes),
            
            // Limit the reported user info for privacy
            'reported_user'         => $this->whenLoaded('reportedUser', fn() => $this->reportedUser ? [
                'id'                  => $this->reportedUser->id,
                'username'            => $this->reportedUser->username,
                'full_name'           => $this->reportedUser->full_name,
                'profile_picture_url' => $this->reportedUser->profile_picture_url,
            ] : null),

            'resolved_at'           => $this->resolved_at?->toISOString(),
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
