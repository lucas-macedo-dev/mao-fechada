<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class RelaunchRecurringTransactions extends Command
{
    protected $signature = 'transactions:relaunch-recurring';

    protected $description = 'Generate the current cycle transaction for each due active recurring transaction rule';

    public function handle(): void
    {
        $today = Carbon::today();
        $generated = 0;

        RecurringTransaction::query()
            ->where('status', 'active')
            ->chunkById(100, function ($rules) use ($today, &$generated): void {
                foreach ($rules as $rule) {
                    try {
                        if ($this->relaunch($rule, $today)) {
                            $generated++;
                        }
                    } catch (Throwable $e) {
                        Log::error('Failed to relaunch recurring transaction', [
                            'recurring_transaction_id' => $rule->id,
                            'message'                  => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Generated {$generated} recurring transaction(s).");
    }

    private function relaunch(RecurringTransaction $rule, Carbon $today): bool
    {
        if ($rule->last_generated_at !== null && $rule->last_generated_at->isSameMonth($today)) {
            return false;
        }

        $lastDayOfMonth = $today->clone()->endOfMonth()->day;
        $day = min($rule->day_of_month, $lastDayOfMonth);

        if ($today->day < $day) {
            return false;
        }

        $transactedAt = $today->clone()->day($day);

        $rule->transactions()->create([
            'user_id'                  => $rule->user_id,
            'category_id'              => $rule->category_id,
            'type'                     => $rule->type,
            'payment_method'           => $rule->payment_method,
            'amount'                   => $rule->amount,
            'transacted_at'            => $transactedAt->format('Y-m-d'),
            'notes'                    => $rule->notes,
            'recurring_transaction_id' => $rule->id,
        ]);

        $rule->update(['last_generated_at' => $transactedAt->format('Y-m-d')]);

        return true;
    }
}
