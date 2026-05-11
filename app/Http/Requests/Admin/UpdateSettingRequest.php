<?php

namespace App\Http\Requests\Admin;

use App\DTOs\Admin\UpdateSettingDTO;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gated by AdminPolicy (administrate) in controller
    }

    public function rules(): array
    {
        return [
            // setting_value is stored as JSON; we accept any scalar or array
            'setting_value' => ['required'],
            'change_reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function getDto(): UpdateSettingDTO
    {
        $validated = $this->validated();

        return new UpdateSettingDTO(
            settingValue:  $validated['setting_value'],
            changeReason:  $validated['change_reason'] ?? null,
        );
    }
}
