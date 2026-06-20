<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan_code')->default('free');
            $table->enum('status', ['inactive', 'trialing', 'active', 'past_due', 'canceled'])->default('inactive');
            $table->string('provider')->nullable();
            $table->string('provider_customer_id')->nullable();
            $table->string('provider_subscription_id')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->unique(['provider', 'provider_subscription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
