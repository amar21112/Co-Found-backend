<?php

namespace App\Exceptions\Skill;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DuplicateSkillException extends Exception
{
    public function __construct()
    {
        parent::__construct('You already have this skill on your profile.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], Response::HTTP_CONFLICT);
    }
}
