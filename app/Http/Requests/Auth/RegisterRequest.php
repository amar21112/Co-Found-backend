<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\RegisterDTO;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username'              => ['required', 'string', 'min:3', 'max:30',
                'unique:users,username', 'regex:/^[a-zA-Z0-9_]+$/'],
            'password'              => ['required', 'string', 'min:8', 'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
            'password_confirmation' => ['required', 'string'],
            'full_name'             => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex'  => 'Username may only contain letters, numbers, and underscores.',
            'password.regex'  => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
        ];
    }

    public function getDto(): RegisterDTO
    {
        return new RegisterDTO(
            email:    $this->validated('email'),
            username: $this->validated('username'),
            password: $this->validated('password'),
            fullName: $this->validated('full_name'),
        );
    }
}
