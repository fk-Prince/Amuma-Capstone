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
        Schema::table('branches', function (Blueprint $table) {
            $table->string('description', 1000)->change();
        });
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('description', 1000)->change();
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('description', 255)->change();
        });
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('description', 255)->change();
        });
    }
};
