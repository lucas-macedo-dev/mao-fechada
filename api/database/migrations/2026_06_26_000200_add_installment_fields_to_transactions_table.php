<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->string('installment_group_id', 36)->nullable()->after('payment_method');
            $table->tinyInteger('installment_number')->unsigned()->nullable()->after('installment_group_id');
            $table->tinyInteger('installment_total')->unsigned()->nullable()->after('installment_number');

            $table->index(['user_id', 'installment_group_id']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'installment_group_id']);
            $table->dropColumn(['installment_group_id', 'installment_number', 'installment_total']);
        });
    }
};
