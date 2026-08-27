<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'    => ['nullable', 'integer', 'exists:categories,id'],
            'type'           => ['nullable', Rule::in(['income', 'expense'])],
            'payment_method' => ['nullable', Rule::in([
                'credit_card',
                'debit_card',
                'cash',
                'pix',
                'bank_slip',
                'bank_transfer',
            ])],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date'],
            'month'       => ['nullable', 'date_format:Y-m'],
            'amount_min'  => ['nullable', 'numeric', 'min:0'],
            'amount_max'  => [
                'nullable',
                'numeric',
                'min:0',
                Rule::when($this->filled('amount_min'), ['gte:amount_min']),
            ],
            'notes'       => ['nullable', 'string', 'max:255'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
            'installment' => ['nullable'],
        ];
    }
}
