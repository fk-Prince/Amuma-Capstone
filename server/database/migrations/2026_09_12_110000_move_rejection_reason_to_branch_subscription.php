<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_subscription', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
        });

        DB::statement(
            'UPDATE branch_subscription bs
             SET rejection_reason = b.rejection_reason
             FROM branches b
             WHERE bs.branch_id = b.branch_id
               AND bs.status = ?
               AND b.rejection_reason IS NOT NULL',
            [\App\Models\BranchSubscription::STATUS_REJECTED]
        );

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable();
        });

        DB::statement(
            'UPDATE branches b
             SET rejection_reason = bs.rejection_reason
             FROM branch_subscription bs
             WHERE bs.branch_id = b.branch_id
               AND bs.rejection_reason IS NOT NULL'
        );

        Schema::table('branch_subscription', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
