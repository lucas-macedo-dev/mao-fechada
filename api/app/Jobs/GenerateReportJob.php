<?php

namespace App\Jobs;

use App\Models\Report;
use App\Support\Reports\CsvReportWriter;
use App\Support\Reports\PdfReportWriter;
use App\Support\TransactionFilterQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $reportId) {}

    public function handle(CsvReportWriter $csvWriter, PdfReportWriter $pdfWriter): void
    {
        $report = Report::query()->find($this->reportId);

        if (! $report || in_array($report->status, ['completed', 'failed'], true)) {
            return;
        }

        $report->update(['status' => 'processing']);

        try {
            $query = TransactionFilterQuery::apply(
                $report->user->transactions(),
                $report->filters ?? []
            );

            $contents = match ($report->format) {
                'csv' => $csvWriter->write($query),
                'pdf' => $pdfWriter->write($query, $report->user, $report->filters ?? []),
            };

            $path = "reports/{$report->user_id}/{$report->id}.{$report->format}";
            Storage::disk('local')->put($path, $contents);

            $report->update([
                'status'       => 'completed',
                'file_path'    => $path,
                'completed_at' => now(),
                'expires_at'   => now()->addDays((int) config('reports.expires_after_days')),
            ]);
        } catch (Throwable $e) {
            $report->update([
                'status'         => 'failed',
                'failure_reason' => substr($e->getMessage(), 0, 1000),
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        Report::query()
            ->where('id', $this->reportId)
            ->whereNotIn('status', ['completed', 'failed'])
            ->update([
                'status'         => 'failed',
                'failure_reason' => substr($exception->getMessage(), 0, 1000),
            ]);
    }
}
