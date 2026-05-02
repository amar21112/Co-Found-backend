<?php

namespace App\Exceptions\Verification;

use App\Enums\IdentityVerificationStatus;
use RuntimeException;

class VerificationAlreadyExistsException extends RuntimeException
{
    public function __construct(public readonly IdentityVerificationStatus $currentStatus)
    {
        $message = match($currentStatus) {
            IdentityVerificationStatus::Pending     => 'You already have a verification submission pending review.',
            IdentityVerificationStatus::UnderReview => 'Your verification is currently under review. Please wait for the outcome.',
            IdentityVerificationStatus::Escalated   => 'Your verification has been escalated for senior review. Please wait.',
            IdentityVerificationStatus::Verified    => 'Your identity is already verified.',
            default                                  => 'A verification submission already exists.',
        };

        parent::__construct($message);
    }
}
