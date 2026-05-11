<?php

namespace App\Enums;

enum FeedbackType: string
{
    case Relevant          = 'relevant';
    case NotRelevant       = 'not_relevant';
    case AlreadyConnected  = 'already_connected';
    case NotInterested     = 'not_interested';

    /**
     * Whether this feedback signals a positive signal for ML training.
     * Used to weight training samples.
     */
    public function isPositive(): bool
    {
        return $this === self::Relevant;
    }

    /**
     * Human-readable label for export/reporting.
     */
    public function label(): string
    {
        return match($this) {
            self::Relevant         => 'Relevant',
            self::NotRelevant      => 'Not Relevant',
            self::AlreadyConnected => 'Already Connected',
            self::NotInterested    => 'Not Interested',
        };
    }
}
