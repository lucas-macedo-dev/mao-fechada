<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->enum('payment_method', [
                'cartao_credito', 'cartao_debito', 'dinheiro', 'pix', 'boleto', 'ted',
                'credit_card', 'debit_card', 'cash', 'bank_slip', 'bank_transfer',
            ])->default('pix')->change();
        });

        DB::statement("UPDATE transactions SET payment_method = CASE payment_method
            WHEN 'cartao_credito' THEN 'credit_card'
            WHEN 'cartao_debito' THEN 'debit_card'
            WHEN 'dinheiro' THEN 'cash'
            WHEN 'boleto' THEN 'bank_slip'
            WHEN 'ted' THEN 'bank_transfer'
            ELSE payment_method
        END");

        Schema::table('transactions', function (Blueprint $table): void {
            $table->enum('payment_method', [
                'credit_card', 'debit_card', 'cash', 'pix', 'bank_slip', 'bank_transfer',
            ])->default('pix')->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->enum('payment_method', [
                'cartao_credito', 'cartao_debito', 'dinheiro', 'pix', 'boleto', 'ted',
                'credit_card', 'debit_card', 'cash', 'bank_slip', 'bank_transfer',
            ])->default('pix')->change();
        });

        DB::statement("UPDATE transactions SET payment_method = CASE payment_method
            WHEN 'credit_card' THEN 'cartao_credito'
            WHEN 'debit_card' THEN 'cartao_debito'
            WHEN 'cash' THEN 'dinheiro'
            WHEN 'bank_slip' THEN 'boleto'
            WHEN 'bank_transfer' THEN 'ted'
            ELSE payment_method
        END");

        Schema::table('transactions', function (Blueprint $table): void {
            $table->enum('payment_method', [
                'cartao_credito', 'cartao_debito', 'dinheiro', 'pix', 'boleto', 'ted',
            ])->default('pix')->change();
        });
    }
};
