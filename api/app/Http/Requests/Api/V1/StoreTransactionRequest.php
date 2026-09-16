<?php

declare(strict_types=1);

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
            'category_id'    => ['required', 'integer', 'exists:categories,id'],
            'type'           => ['required', Rule::in(['income', 'expense'])],
            'payment_method' => ['required', Rule::in([
                'credit_card',
                'debit_card',
                'cash',
                'pix',
                'bank_slip',
                'bank_transfer',
            ])],
            'amount'             => ['required', 'numeric', 'gt:0'],
            'transacted_at'      => ['required', 'date'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'installment_number' => ['nullable', 'integer', 'min:1', 'max:60', 'lte:installment_total', 'prohibited_if:recurring,true'],
            'installment_total'  => ['nullable', 'integer', 'min:1', 'max:60', 'prohibited_if:recurring,true'],
            'recurring'          => ['nullable', 'boolean'],
        ];
    }
}
