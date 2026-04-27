<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'setting_key'   => $this->setting_key,
            'setting_value' => $this->setting_value,
            'setting_type'  => $this->setting_type,
            'description'   => $this->description,
            'is_public'     => $this->is_public,
            'updated_by'    => $this->whenLoaded('updatedBy', fn() =>
                $this->updatedBy ? [
                    'id'       => $this->updatedBy->id,
                    'username' => $this->updatedBy->username,
                    'full_name'=> $this->updatedBy->full_name,
                ] : null
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
