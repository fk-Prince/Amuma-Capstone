<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_staff_requests', function (Blueprint $table) {
            $table->id('booking_staff_request_id');
            $table->foreignId('booking_id')
                ->constrained('bookings', 'booking_id')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees', 'employee_id')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['booking_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_staff_requests');
    }
};
