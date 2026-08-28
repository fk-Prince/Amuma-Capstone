<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id('conversation_id');

            $table->string('type', 20)->default('family');

            $table->foreignId('branch_id')
                ->constrained('branches', 'branch_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients', 'client_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->nullable()
                ->constrained('patients', 'patient_id')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('employee_one_id')
                ->nullable()
                ->constrained('employees', 'employee_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('employee_two_id')
                ->nullable()
                ->constrained('employees', 'employee_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'client_id'], 'conversations_family_unique');
            $table->unique(
                ['branch_id', 'employee_one_id', 'employee_two_id'],
                'conversations_staff_pair_unique'
            );
            $table->index(['branch_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
