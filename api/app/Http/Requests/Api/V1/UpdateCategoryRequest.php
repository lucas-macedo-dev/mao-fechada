<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['sometimes', 'required', 'string', 'max:100'],
            'type'      => ['sometimes', 'required', Rule::in(['income', 'expense'])],
            'icon'      => ['nullable', 'string', 'max:100'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
        ];
    }
}
