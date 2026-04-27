<?php

namespace App\Http\Requests\Admin;

use App\DTOs\Admin\UpdateAdminUserDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gated by AdminPolicy (administrate) in controller
    }

    public function rules(): array
    {
        return [
            'role' => [
                'sometimes',
                'string',
                Rule::in(['guest', 'regular_user', 'moderator', 'administrator']),
            ],
            'account_status' => [
                'sometimes',
                'string',
                Rule::in(['pending', 'active', 'suspended', 'banned', 'deleted']),
            ],
        ];
    }

    public function getDto(): UpdateAdminUserDTO
    {
        $validated = $this->validated();

        return new UpdateAdminUserDTO(
            role:          $validated['role']           ?? null,
            accountStatus: $validated['account_status'] ?? null,
        );
    }
}
