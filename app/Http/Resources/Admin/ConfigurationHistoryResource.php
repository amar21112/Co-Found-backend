<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigurationHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'setting_key'   => $this->setting_key,
            'old_value'     => $this->old_value,
            'new_value'     => $this->new_value,
            'change_reason' => $this->change_reason,
            'changed_by'    => $this->whenLoaded('changedBy', fn() =>
                $this->changedBy ? [
                    'id'       => $this->changedBy->id,
                    'username' => $this->changedBy->username,
                    'full_name'=> $this->changedBy->full_name,
                ] : null
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
