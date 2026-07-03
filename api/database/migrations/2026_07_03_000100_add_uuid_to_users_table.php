<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('uuid', 36)->nullable()->unique()->after('id');
        });

        DB::table('users')->whereNull('uuid')->orderBy('id')->each(function ($user): void {
            DB::table('users')->where('id', $user->id)->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('uuid', 36)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });
    }
};
