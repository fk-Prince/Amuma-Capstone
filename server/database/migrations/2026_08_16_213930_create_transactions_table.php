<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->string('transaction_code')->unique();
            $table->string('transaction_reference_id')->nullable()->index();

            $table->foreignId('branch_id')
                ->constrained('branches', 'branch_id')
                ->cascadeOnUpdate();

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

            $table->decimal('amount', 10, 2);

            $table->enum('type', ['payment', 'withdraw']);
            $table->enum('direction', ['credit', 'debit']);

            $table->enum('status', ['completed', 'requested', 'rejected'])
                ->default('completed')
                ->index();

            $table->string('method', 50)->nullable();
            $table->string('masked_account_number', 25)->nullable();
            $table->text('declined_reason')->nullable();

            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['branch_id', 'created_at']);
            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
