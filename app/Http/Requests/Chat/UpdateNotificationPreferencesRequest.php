<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'platform_notifications' => 'sometimes|boolean',
            'email_notifications'    => 'sometimes|boolean',
            'push_notifications'     => 'sometimes|boolean',
            'notification_digest'    => 'sometimes|string|in:immediate,hourly,daily,weekly,none',
            'quiet_hours_start'      => 'sometimes|nullable|date_format:H:i',
            'quiet_hours_end'        => 'sometimes|nullable|date_format:H:i',
            'quiet_hours_timezone'   => 'sometimes|nullable|string|max:50|timezone',
            'preferences'            => 'sometimes|array',
        ];
    }
}
