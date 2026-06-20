<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->enum('payment_method', [
                'cartao_credito',
                'cartao_debito',
                'dinheiro',
                'pix',
                'boleto',
                'ted',
            ])->default('pix');

            $table->index(['user_id', 'category_id', 'transacted_at']);
            $table->index(['user_id', 'payment_method', 'transacted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'category_id', 'transacted_at']);
            $table->dropIndex(['user_id', 'payment_method', 'transacted_at']);
            $table->dropColumn('payment_method');
        });
    }
};
