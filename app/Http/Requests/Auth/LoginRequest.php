<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\LoginDTO;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function getDto(): LoginDTO
    {
        return new LoginDTO(
            email:    $this->validated('email'),
            password: $this->validated('password'),
        );
    }
}
