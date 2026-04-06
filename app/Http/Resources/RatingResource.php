<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'communication_rating'   => $this->communication_rating,
            'reliability_rating'     => $this->reliability_rating,
            'skill_rating'           => $this->skill_rating,
            'problem_solving_rating' => $this->problem_solving_rating,
            'teamwork_rating'        => $this->teamwork_rating,
            'overall_rating'         => $this->overall_rating,
            'written_feedback'       => $this->written_feedback,
            'visibility'             => $this->visibility,
            'rater'                  => new UserResource($this->whenLoaded('rater')),
            'rated_user'             => new UserResource($this->whenLoaded('ratedUser')),
            'project'                => $this->whenLoaded('project'),
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }
}
