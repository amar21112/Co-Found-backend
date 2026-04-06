<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'file_url'      => $this->file_url,
            'thumbnail_url' => $this->thumbnail_url,
            'item_type'     => $this->item_type,
            'external_url'  => $this->external_url,
            'visibility'    => $this->visibility,
            'is_featured'   => $this->is_featured,
            'skills'        => $this->whenLoaded('skills', fn() =>
                $this->skills->pluck('skill_name')
            ),
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
