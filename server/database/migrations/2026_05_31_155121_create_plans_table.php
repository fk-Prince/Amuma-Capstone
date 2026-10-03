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
        Schema::create('plans', function (Blueprint $table) {
            $table->id('plan_id');
            $table->string('description');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->decimal('additional_branch_price', 10, 2)->default(0);
            $table->string('plan_code')->index();
            $table->enum('type', ['sme', 'enterprise']);
            $table->unique(['plan_code', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
