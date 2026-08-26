<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'reason')) {
            Schema::table('bookings', function (Blueprint $table) {
                // Why a booking was rejected. Nullable: only set on rejection.
                $table->text('reason')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'reason')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }
    }
};
