<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id('invoice_id');
            $table->string('invoice_code')->unique();
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'void', 'written_off'])
                ->default('unpaid');
            $table->foreignId('branch_id')
                ->constrained('branches', 'branch_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->foreign('voided_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
            $table->timestamp('written_off_at')->nullable();
            $table->unsignedBigInteger('written_off_by')->nullable();
            $table->text('write_off_reason')->nullable();
            $table->foreign('written_off_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
