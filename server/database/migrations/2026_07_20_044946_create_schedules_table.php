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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id('schedule_id');
            $table->string('schedule_code', 20)->unique();
            $table->foreignId('scheduled_location_id')
                ->constrained('locations', 'location_id');
            $table->foreignId('patient_id')
                ->constrained('patients', 'patient_id');
            $table->timestamp('scheduled_at');
            $table->string('status', 50)->nullable();
            $table->enum('category', ['Homecare', 'Facility']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
