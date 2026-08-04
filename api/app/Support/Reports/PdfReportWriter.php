<?php

namespace App\Support\Reports;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Database\Eloquent\Builder;

class PdfReportWriter
{
    public function write(Builder $query, User $user, array $filters): string
    {
        $transactions = $query->with('category')
            ->orderBy('transacted_at')
            ->orderBy('id')
            ->get();

        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->type === 'income') {
                $totalIncome += $transaction->amount;
            } else {
                $totalExpense += $transaction->amount;
            }
        }

        return Pdf::loadView('reports.transactions-pdf', [
            'user' => $user,
            'transactions' => $transactions,
            'filters' => $filters,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalNet' => $totalIncome - $totalExpense,
        ])->output();
    }
}
