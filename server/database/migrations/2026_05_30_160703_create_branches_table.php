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
        Schema::create('branches', function (Blueprint $table) {
            $table->id('branch_id');
            $table->uuid('uuid')->unique();
            $table->foreignId('owner_user_id')->constrained('users', 'user_id');
            $table->foreignId('agency_id')->nullable()->constrained('agencies', 'agency_id');
            $table->foreignId('location_id')->nullable()->constrained('locations', 'location_id');
            $table->string('name')->unique();
            $table->boolean('is_verified')->default(false);
            $table->string('description');
            $table->json('settings')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('image')->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
