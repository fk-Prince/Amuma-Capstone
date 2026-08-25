<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guarded because the create-invoices migration no longer adds this
        // column, so a fresh `migrate:fresh` reaches here with nothing to drop.
        if (Schema::hasColumn('invoices', 'is_collected')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('is_collected');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('invoices', 'is_collected')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->boolean('is_collected')->default(false);
            });
        }
    }
};
