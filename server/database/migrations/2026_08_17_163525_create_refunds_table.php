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

            $table->foreignId('allocation_id')
                ->constrained('payment_invoice_allocation', 'allocation_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('refund_code')->nullable();

            $table->decimal('amount', 10, 2);
            $table->string('refund_method', 50)->nullable();
            $table->string('masked_card_number', 25)->nullable();

            $table->enum('status', ['requested', 'completed', 'declined'])
                ->default('requested');

            $table->text('declined_reason')->nullable();
            $table->timestamps();
            $table->index('reference_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
