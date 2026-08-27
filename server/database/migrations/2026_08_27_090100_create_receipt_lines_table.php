<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_lines', function (Blueprint $table) {
            $table->id('receipt_line_id');

            $table->foreignId('receipt_id')
                ->constrained('payment_receipts', 'receipt_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('payment_id')
                ->constrained('payments', 'payment_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedSmallInteger('line_no');

            $table->decimal('prior_balance', 10, 2);
            $table->decimal('new_balance', 10, 2);
            $table->string('resulting_status', 20);

            $table->unique(['receipt_id', 'line_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_lines');
    }
};
