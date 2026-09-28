<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_subscription', function (Blueprint $table) {
            $table->id('branch_subscription_id');
            $table->uuid('uuid')->unique();
            $table->foreignId('subscription_id')->constrained('subscriptions', 'subscription_id');
            $table->foreignId('branch_id')->constrained('branches', 'branch_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            $table->unique(['subscription_id', 'branch_id']);
            $table->index(['subscription_id', 'status']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_subscription');
    }
};
