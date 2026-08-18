<?php

declare(strict_types=1);

namespace App\Support\Reports;

use Illuminate\Contracts\Database\Eloquent\Builder;

class CsvReportWriter
{
    public function write(Builder $query): string
    {
        $stream = fopen('php://temp', 'w+');

        fputcsv($stream, ['Date', 'Category', 'Type', 'Payment Method', 'Amount', 'Notes'], escape: '\\');

        $query->with('category')
            ->orderBy('transacted_at')
            ->orderBy('id')
            ->chunkById(200, function ($transactions) use ($stream): void {
                foreach ($transactions as $transaction) {
                    fputcsv($stream, [
                        $transaction->transacted_at->format('Y-m-d'),
                        $transaction->category?->name,
                        $transaction->type,
                        $transaction->payment_method,
                        $transaction->amount,
                        $transaction->notes,
                    ], escape: '\\');
                }
            });

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return $contents;
    }
}
