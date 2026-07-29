<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('online_schedules', function (Blueprint $table) {
            $table->id('online_schedule_id');
            $table->foreignId('schedule_assigned_id')
                ->constrained('schedule_assigned', 'schedule_assigned_id')
                ->cascadeOnDelete();
            $table->string('qr_in')->nullable();
            $table->string('qr_out')->nullable();
            $table->string('notes')->nullable();
            $table->timestamp('in_timestamp')->nullable();
            $table->timestamp('out_timestamp')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_schedules');
    }
};
