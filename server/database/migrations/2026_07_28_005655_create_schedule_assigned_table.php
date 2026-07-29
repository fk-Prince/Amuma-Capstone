<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_assigned', function (Blueprint $table) {
            $table->id('schedule_assigned_id');

            $table->foreignId('schedule_services_id')
                ->constrained('schedule_services', 'schedule_services_id')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees', 'employee_id')
                ->cascadeOnDelete();

            $table->string('role', 50)->nullable();

            $table->timestamps();

            $table->unique(['schedule_services_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_assigneds');
    }
};
