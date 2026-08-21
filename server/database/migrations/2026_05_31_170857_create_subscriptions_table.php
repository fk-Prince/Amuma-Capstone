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
            $table->foreignId('branch_id')->constrained('branches', 'branch_id');
            $table->enum('billing_interval', ['YEARLY', 'MONTHLY']);
            $table->enum('status', ['active', 'inactive', 'expired', 'pending', 'rejected'])->default('pending');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
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
