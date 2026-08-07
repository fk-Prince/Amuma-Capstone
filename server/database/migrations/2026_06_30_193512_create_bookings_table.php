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
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id');
            $table->foreignId('branch_id')->constrained('branches', 'branch_id');
            $table->json('booking_data');
            $table->enum('category', ['homecare', 'facility']);
            $table->enum('status', ['approved', 'pending', 'rejected', 'expired',  'cancelled'])->default('pending');
            $table->enum('booking_type', ['walk_in', 'online']);
            $table->dateTime('valid_until');
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
