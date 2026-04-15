<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectMilestoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'project_id'     => $this->project_id,
            'title'          => $this->title,
            'description'    => $this->description,
            'due_date'       => $this->due_date?->toDateString(),
            'completed_date' => $this->completed_date?->toDateString(),
            'status'         => $this->status,
            'order_index'    => $this->order_index,
            'is_completed'   => $this->isCompleted(),
            'is_overdue'     => $this->isOverdue(),
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
