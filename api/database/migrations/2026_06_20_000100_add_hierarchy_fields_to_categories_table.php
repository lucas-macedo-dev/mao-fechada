<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();
            $table->string('icon')->nullable();

            $table->dropUnique(['user_id', 'name', 'type']);
            $table->unique(['user_id', 'parent_id', 'name']);
            $table->index(['user_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'parent_id']);
            $table->dropUnique(['user_id', 'parent_id', 'name']);
            $table->unique(['user_id', 'name', 'type']);

            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('icon');
        });
    }
};
