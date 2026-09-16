<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreignId('recurring_transaction_id')
                ->nullable()
                ->after('installment_total')
                ->constrained('recurring_transactions')
                ->restrictOnDelete();

            $table->index(['user_id', 'recurring_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'recurring_transaction_id']);
            $table->dropConstrainedForeignId('recurring_transaction_id');
        });
    }
};
