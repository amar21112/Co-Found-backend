<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EndorsementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'endorsed_by'=> new UserResource($this->whenLoaded('endorser')),
            'created_at' => $this->created_at,
        ];
    }
}
