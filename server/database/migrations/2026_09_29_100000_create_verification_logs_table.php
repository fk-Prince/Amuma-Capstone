<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_logs', function (Blueprint $table) {
            $table->id('verification_log_id');
            $table->foreignId('branch_subscription_id')
                ->constrained('branch_subscription', 'branch_subscription_id')
                ->cascadeOnDelete();
            $table->enum('action', ['approved', 'rejected']);
            $table->enum('scope', ['branch', 'agency', 'both'])->default('branch');
            $table->text('reason')->nullable();
            $table->foreignId('action_by')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
            $table->timestamps();
            $table->index(['branch_subscription_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_logs');
    }
};
