<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\ForgotPasswordDTO;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function getDto(): ForgotPasswordDTO
    {
        return new ForgotPasswordDTO(
            email: $this->validated('email'),
        );
    }
}
