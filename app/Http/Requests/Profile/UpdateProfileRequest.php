<?php

namespace App\Http\Requests\Profile;

use App\Traits\ResolvesUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * UpdateProfileRequest
 *
 * Handles both text fields and an optional profile picture upload.
 *
 * Content-Type: multipart/form-data  (required when uploading an image)
 * Content-Type: application/json     (allowed when no image is included)
 *
 * The profile_picture field accepts an image file (JPEG, PNG, WebP, GIF).
 * Max size: 2 MB. Min dimensions: 50×50 px to reject tiny corrupt files.
 */
class UpdateProfileRequest extends FormRequest
{
    use ResolvesUser;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user   = $this->resolveUser($this);
        $userId = $user->id;

        return [
            'username' => [
                'sometimes',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'full_name'           => ['sometimes', 'string', 'max:100'],
            'bio'                 => ['sometimes', 'nullable', 'string', 'max:1000'],
            'location'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'website_url'         => ['sometimes', 'nullable', 'url', 'max:255'],
            'linkedin_url'        => ['sometimes', 'nullable', 'url', 'max:255'],
            'github_url'          => ['sometimes', 'nullable', 'url', 'max:255'],

            // ── Profile picture — image upload, not a URL string ──────────────
            'profile_picture' => [
                'sometimes',
                'nullable',
                File::image()
                    ->max(2 * 1024)          // 2 MB
                    ->dimensions(
                        Rule::dimensions()
                            ->minWidth(50)
                            ->minHeight(50)
                    ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_picture.image'      => 'The profile picture must be an image.',
            'profile_picture.max'        => 'The profile picture must not exceed 2 MB.',
            'profile_picture.dimensions' => 'The profile picture must be at least 50×50 pixels.',
            'profile_picture.mimes'      => 'The profile picture must be a JPEG, PNG, WebP, or GIF.',
        ];
    }
}
