<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', [
                'credit_card',
                'debit_card',
                'cash',
                'pix',
                'bank_slip',
                'bank_transfer',
            ]);
            $table->text('notes')->nullable();
            $table->tinyInteger('day_of_month')->unsigned();
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->date('last_generated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
