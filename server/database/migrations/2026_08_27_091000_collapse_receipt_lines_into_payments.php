<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payments', 'prior_balance')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('prior_balance', 10, 2)->nullable();
                $table->decimal('new_balance', 10, 2)->nullable();
            });
        }

        if (Schema::hasTable('receipt_lines')) {
            foreach (DB::table('receipt_lines')->whereNotNull('payment_id')->get() as $line) {
                DB::table('payments')
                    ->where('payment_id', $line->payment_id)
                    ->update([
                        'prior_balance' => $line->prior_balance,
                        'new_balance' => $line->new_balance,
                    ]);
            }

            Schema::dropIfExists('receipt_lines');
        }

        foreach (
            ['amount_applied', 'change_due', 'balance_after', 'payment_method', 'masked_account']
            as $column
        ) {
            if (Schema::hasColumn('payment_receipts', $column)) {
                Schema::table('payment_receipts', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['prior_balance', 'new_balance']);
        });

        Schema::table('payment_receipts', function (Blueprint $table) {
            $table->decimal('amount_applied', 10, 2)->default(0);
            $table->decimal('change_due', 10, 2)->default(0);
            $table->decimal('balance_after', 10, 2)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->string('masked_account', 25)->nullable();
        });
    }
};
