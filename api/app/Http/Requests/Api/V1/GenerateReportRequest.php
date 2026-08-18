<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format'         => ['required', Rule::in(['csv', 'pdf'])],
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
            'installment' => ['nullable'],
        ];
    }
}
