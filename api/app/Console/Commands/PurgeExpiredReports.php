<?php

namespace App\Console\Commands;

use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredReports extends Command
{
    protected $signature = 'reports:purge-expired';

    protected $description = 'Delete completed reports (and their files) past their expiry date';

    public function handle(): void
    {
        $purged = 0;

        Report::query()
            ->where('status', 'completed')
            ->where('expires_at', '<', now())
            ->chunkById(100, function ($reports) use (&$purged): void {
                foreach ($reports as $report) {
                    if ($report->file_path) {
                        Storage::disk('local')->delete($report->file_path);
                    }

                    $report->delete();
                    $purged++;
                }
            });

        $this->info("Purged {$purged} expired reports.");
    }
}
