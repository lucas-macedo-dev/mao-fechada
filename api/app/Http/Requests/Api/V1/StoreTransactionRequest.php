<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'type' => ['required', Rule::in(['entrada', 'saida', 'income', 'expense'])],
            'payment_method' => ['required', Rule::in([
                'cartao_credito',
                'cartao_debito',
                'dinheiro',
                'pix',
                'boleto',
                'ted',
            ])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transacted_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'installment_number' => ['nullable', 'integer', 'min:1', 'max:60', 'lte:installment_total'],
            'installment_total' => ['nullable', 'integer', 'min:1', 'max:60'],
        ];
    }
}
