<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_schedules', function (Blueprint $table) {
            $table->id('medication_schedule_id');
            $table->foreignId('medication_id')
                ->constrained('medications', 'medication_id')
                ->cascadeOnDelete();

            $table->date('date');
            $table->string('time', 5);
            $table->enum('status', ['taken', 'missed', 'removed']);

            $table->foreignId('marked_by')->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();

            $table->timestamp('recorded_at')->useCurrent();

            $table->index(['medication_id', 'date', 'time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_schedules');
    }
};
