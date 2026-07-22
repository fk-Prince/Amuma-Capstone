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


        Schema::create('schedule_services', function (Blueprint $table) {
            $table->id('schedule_services_id');
            $table->foreignId('schedule_id')
                ->constrained('schedules', 'schedule_id');
            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services', 'service_id');
            $table->decimal('hours_booked', 10, 2)
                ->nullable();
            $table->string('status', 50)
                ->nullable();
            $table->enum('type', ['Medical', 'ADL']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_services');
    }
};
