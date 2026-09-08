<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_invoice_allocation', function (Blueprint $table) {
            $table->bigIncrements('allocation_id');
            $table->foreignId('payment_id')
                ->constrained('payments', 'payment_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('invoice_id')
                ->constrained('invoices', 'invoice_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['payment_id', 'invoice_id']);
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_invoice_allocation');
    }
};
