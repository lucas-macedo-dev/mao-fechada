<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'type' => ['sometimes', 'required', Rule::in(['entrada', 'saida', 'income', 'expense'])],
            'payment_method' => ['sometimes', 'required', Rule::in([
                'cartao_credito',
                'cartao_debito',
                'dinheiro',
                'pix',
                'boleto',
                'ted',
            ])],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'transacted_at' => ['sometimes', 'required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
