<?php

namespace App\Http\Requests\Profile;

use App\Traits\ResolvesUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    use ResolvesUser;
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->resolveUser($this);
        $userId = $user->id;

        return [
            'username'            => ['sometimes', 'string', 'max:50', 'alpha_dash',
                                      Rule::unique('users', 'username')->ignore($userId)],
            'full_name'           => ['sometimes', 'string', 'max:100'],
            'bio'                 => ['sometimes', 'nullable', 'string', 'max:1000'],
            'location'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'website_url'         => ['sometimes', 'nullable', 'url', 'max:255'],
            'linkedin_url'        => ['sometimes', 'nullable', 'url', 'max:255'],
            'github_url'          => ['sometimes', 'nullable', 'url', 'max:255'],
            'profile_picture_url' => ['sometimes', 'nullable', 'url', 'max:500'],
        ];
    }
}
