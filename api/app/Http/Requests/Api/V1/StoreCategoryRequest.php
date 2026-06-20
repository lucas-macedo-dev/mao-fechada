<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['entrada', 'saida', 'income', 'expense'])],
            'icon' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }
}
