<?php

namespace App\Exceptions\Skill;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DuplicateEndorsementException extends Exception
{
    public function __construct()
    {
        parent::__construct('You have already endorsed this skill.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], Response::HTTP_CONFLICT);
    }
}
