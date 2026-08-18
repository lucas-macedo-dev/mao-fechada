<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'password'             => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
            'locale'               => ['sometimes', 'required', Rule::in(['pt-BR', 'en'])],
            'profile_photo'        => ['sometimes', 'nullable', 'image', 'max:2048'],
            'remove_profile_photo' => ['sometimes', 'boolean'],
        ];
    }
}
