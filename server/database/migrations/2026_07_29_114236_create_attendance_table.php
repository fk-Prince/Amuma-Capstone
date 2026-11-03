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
        Schema::create('adl_attendance', function (Blueprint $table) {
            $table->id('adl_attendance_id');
            $table->foreignId('schedule_assigned_id')
                ->constrained('schedule_assigned', 'schedule_assigned_id')
                ->cascadeOnDelete();
            $table->enum('type_in', ['scanned', 'force', 'handover'])->default('scanned');
            $table->enum('type_out', ['scanned', 'force', 'handover'])->nullable();
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
        Schema::dropIfExists('adl_attendance');
    }
};
