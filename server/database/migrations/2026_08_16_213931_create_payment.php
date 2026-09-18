<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('payment_id');

            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('transactions', 'transaction_id')
                ->cascadeOnUpdate();
            $table->decimal('cash_tendered', 10, 2)->nullable();
            $table->decimal('prior_balance', 10, 2)->nullable();
            $table->decimal('new_balance', 10, 2)->nullable();
            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users', 'user_id')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
