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
        Schema::create('modules', function (Blueprint $table) {
            $table->id('module_id');
            $table->string('module_name');
            $table->string('description');
            $table->boolean('has_create')->default(true);
            $table->boolean('has_read')->default(true);
            $table->boolean('has_update')->default(true);
            $table->boolean('has_approve')->default(true);
            $table->boolean('has_assign')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
