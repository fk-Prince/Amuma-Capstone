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
        Schema::create('agencies', function (Blueprint $table) {
            $table->id('agency_id')->index();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('image')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations', 'location_id');
            $table->foreignId('registered_by')->constrained('users', 'user_id');
            $table->string('description', 1000)->nullable();
            $table->string('id_front')->nullable();
            $table->string('id_back')->nullable();
            $table->string('document')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
