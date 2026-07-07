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
        Schema::create('services', function (Blueprint $table) {
            $table->id('service_id');
            $table->uuid('service_uuid')->unique();
            $table->foreignId('branch_id')->constrained('branches', 'branch_id');
            $table->foreignId('category_id')->constrained('categories', 'category_id');
            $table->decimal('price', 10, 2);
            $table->string('service_name');
            $table->time('maximum_duration');
            $table->boolean('is_available')->default(true);
            $table->enum('type', ['online', 'facility', 'both'])->default('both');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
