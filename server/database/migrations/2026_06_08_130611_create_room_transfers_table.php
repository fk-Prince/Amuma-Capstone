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
        Schema::create('room_transfers', function (Blueprint $table) {
            $table->id('room_transfer_id');
            $table->foreignId('patient_admission_id')->constrained('patient_admissions', 'patient_admission_id');
            $table->foreignId('from_room_id')->constrained('rooms', 'room_id');
            $table->foreignId('to_room_id')->constrained('rooms', 'room_id');
            $table->foreignId('from_bed_id')->constrained('beds', 'bed_id');
            $table->foreignId('to_bed_id')->constrained('beds', 'bed_id');
            $table->string('reason')->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_transfers');
    }
};
