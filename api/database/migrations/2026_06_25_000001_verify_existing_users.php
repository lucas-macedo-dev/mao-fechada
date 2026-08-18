<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->isProduction()) {
            DB::table('users')
                ->whereNull('email_verified_at')
                ->update(['email_verified_at' => now()]);
        }
    }

    public function down(): void
    {
        // intentionally empty — this is a one-way data fix
    }
};
