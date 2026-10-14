<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_notes', function (Blueprint $table) {
            $table->id('employee_note_id');
            $table->uuid('uuid')->unique();
            $table->foreignId('branch_id')
                ->constrained('branches', 'branch_id')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees', 'employee_id')
                ->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()
                ->constrained('schedules', 'schedule_id')
                ->nullOnDelete();
            $table->foreignId('patient_id')->nullable()
                ->constrained('patients', 'patient_id')
                ->nullOnDelete();
            $table->text('note');
            $table->timestamps();
            $table->index(['branch_id', 'employee_id']);
            $table->index('schedule_id');
            $table->index('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_notes');
    }
};
