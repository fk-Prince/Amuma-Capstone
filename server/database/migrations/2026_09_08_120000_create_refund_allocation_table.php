<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id('refund_id');

            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('transactions', 'transaction_id')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->decimal('amount', 10, 2);
            $table->timestamp('created_at')->nullable();

            $table->index('transaction_id');
        });

        Schema::create('refund_allocation', function (Blueprint $table) {
            $table->id('refund_allocation_id');

            $table->foreignId('refund_id')
                ->constrained('refunds', 'refund_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('allocation_id')
                ->constrained('payment_invoice_allocation', 'allocation_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('invoice_adjustment_id')
                ->nullable()
                ->constrained('invoice_adjustments', 'invoice_adjustment_id')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->decimal('amount', 10, 2);

            $table->unique(['refund_id', 'allocation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_allocation');
        Schema::dropIfExists('refunds');
    }
};
