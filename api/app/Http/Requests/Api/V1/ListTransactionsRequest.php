<?php

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
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['nullable', Rule::in(['entrada', 'saida', 'income', 'expense'])],
            'payment_method' => ['nullable', Rule::in([
                'cartao_credito',
                'cartao_debito',
                'dinheiro',
                'pix',
                'boleto',
                'ted',
            ])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
