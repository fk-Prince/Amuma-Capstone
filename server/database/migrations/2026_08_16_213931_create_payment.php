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
            $table->string('receipt_no')->nullable()->unique();
            $table->string('reference_id')->nullable()->unique();

            $table->foreignId('branch_id')
                ->constrained('branches', 'branch_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('patient_id')
                ->nullable()
                ->constrained('patients', 'patient_id')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients', 'client_id')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('payor_name')->nullable();

            $table->decimal('amount', 10, 2);

            $table->decimal('prior_balance', 10, 2)->nullable();
            $table->decimal('new_balance', 10, 2)->nullable();


            $table->string('payment_method', 50)->nullable();
            $table->string('masked_card_number', 25)->nullable();

            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users', 'user_id')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();
            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
