<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique('conversations_family_unique');
            $table->unique(
                ['branch_id', 'client_id', 'employee_one_id'],
                'conversations_family_staff_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique('conversations_family_staff_unique');
            $table->unique(['branch_id', 'client_id'], 'conversations_family_unique');
        });
    }
};
