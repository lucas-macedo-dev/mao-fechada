<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_limits', function (Blueprint $table): void {
            $table->id();
            $table->string('plan_code');
            $table->string('feature_key');
            $table->unsignedInteger('limit_value')->nullable();
            $table->boolean('is_enforced')->default(false);
            $table->timestamps();

            $table->unique(['plan_code', 'feature_key']);
            $table->index('is_enforced');
        });

        Schema::create('billing_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider');
            $table->string('event_type');
            $table->string('external_id')->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'event_type']);
            $table->unique(['provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_events');
        Schema::dropIfExists('plan_limits');
    }
};
