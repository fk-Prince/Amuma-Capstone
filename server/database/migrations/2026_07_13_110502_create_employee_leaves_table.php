<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_leaves', function (Blueprint $table) {
            $table->id('employee_leave_id');
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_branch_id')
                ->constrained('employee_branches', 'employee_branch_id');
            $table->enum('type', ['sick', 'vacation', 'emergency', 'personal', 'other']);
            $table->string('reason');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users', 'user_id');
            $table->foreignId('returned_by')
                ->nullable()
                ->constrained('users', 'user_id');
            $table->timestamps();

            $table->index(['employee_branch_id', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_leaves');
    }
};
