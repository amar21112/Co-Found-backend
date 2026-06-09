<?php

namespace App\Traits;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

trait SendsEmail
{
    /**
     * Queue a notification email, respecting the user's email_notifications
     * preference. Auth emails (verify, reset) bypass this — pass
     * $respectPreference = false for those.
     */
    protected function sendEmail(
        User     $recipient,
        Mailable $mail,
        bool     $respectPreference = true,
    ): void {
        if ($respectPreference && ! $recipient->notificationPreferences->email_notifications) {
            return;
        }

        Mail::to($recipient->email)->queue($mail);
    }
}
