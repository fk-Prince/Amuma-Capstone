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
            $table->string('reference_id')->nullable()->unique();
            $table->foreignId('invoice_id')
                ->constrained('invoices', 'invoice_id')
                ->cascadeOnDelete();
            $table->string('masked_card_number', 25)
                ->nullable();
            $table->foreignId('receipt_id')
                ->nullable()
                ->constrained('payment_receipts', 'receipt_id')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 50)->nullable();
            $table->decimal('prior_balance', 10, 2)->nullable();
            $table->decimal('new_balance', 10, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
