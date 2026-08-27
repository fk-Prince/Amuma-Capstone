<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('payments', 'resulting_status')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('resulting_status');
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('resulting_status', 20)->nullable();
        });
    }
};
