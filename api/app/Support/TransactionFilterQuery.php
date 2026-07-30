<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TransactionFilterQuery
{
    public static function apply(Builder $query, array $filters): Builder
    {
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['month'])) {
            $monthDate = Carbon::createFromFormat('Y-m', $filters['month']);
            $query
                ->whereYear('transacted_at', $monthDate->year)
                ->whereMonth('transacted_at', $monthDate->month);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('transacted_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('transacted_at', '<=', $filters['date_to']);
        }

        if (isset($filters['installment']) && filter_var($filters['installment'], FILTER_VALIDATE_BOOLEAN)) {
            $query->whereNotNull('installment_group_id');
        }

        return $query;
    }
}
