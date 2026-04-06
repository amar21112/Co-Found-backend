<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePortfolioItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'         => ['sometimes', 'string', 'max:200'],
            'description'   => ['sometimes', 'nullable', 'string', 'max:2000'],
            'file_url'      => ['sometimes', 'nullable', 'url', 'max:500'],
            'thumbnail_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'item_type'     => ['sometimes', 'string', 'in:image,video,document,link,other'],
            'external_url'  => ['sometimes', 'nullable', 'url', 'max:500'],
            'visibility'    => ['sometimes', 'string', 'in:public,private,connections'],
            'is_featured'   => ['sometimes', 'boolean'],
            'skills'        => ['sometimes', 'array', 'max:10'],
            'skills.*'      => ['string', 'max:100'],
        ];
    }
}
