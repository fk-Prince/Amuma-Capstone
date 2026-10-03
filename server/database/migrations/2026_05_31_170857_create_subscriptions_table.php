<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id('subscription_id');
            $table->uuid('uuid')->unique();
            $table->foreignId('plan_id')->constrained('plans', 'plan_id');
            $table->foreignId('agency_id')->constrained('agencies', 'agency_id');
            $table->enum('status', ['active', 'expired', 'pending', 'rejected', 'cancelled'])->default('pending');
            $table->enum('mode', ['test', 'live'])->default('test');
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('pending_plan_id')
                ->nullable()
                ->constrained('plans', 'plan_id');
            $table->date('pending_plan_starts_at')->nullable();
            $table->timestamps();
            $table->index('pending_plan_starts_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
