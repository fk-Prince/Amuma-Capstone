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

        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');
            $table->string('reference_id')->unique();
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->foreignId('branch_id')->constrained('branches', 'branch_id');
            $table->json('booking_data');
            $table->enum('category', ['Homecare', 'Facility']);
            $table->enum('status', ['approved', 'pending', 'rejected', 'expired', 'awaiting'])->default('pending');
            $table->date('valid_until');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
