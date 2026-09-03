<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id('receipt_id');
            $table->string('receipt_no')->unique();

            $table->foreignId('branch_id')
                ->constrained('branches', 'branch_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients', 'patient_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients', 'client_id')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('payor_name')->nullable();

            $table->decimal('amount_tendered', 10, 2);
            $table->decimal('balance_before', 10, 2);

            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users', 'user_id')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('voided_at')->nullable();

            $table->foreignId('voided_by')
                ->nullable()
                ->constrained('users', 'user_id')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('void_reason')->nullable();
            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
    }
};
