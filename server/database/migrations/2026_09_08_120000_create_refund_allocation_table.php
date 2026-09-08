<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_allocation', function (Blueprint $table) {
            $table->id('refund_allocation_id');

            $table->foreignId('refund_id')
                ->constrained('refunds', 'refund_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('allocation_id')
                ->constrained('payment_invoice_allocation', 'allocation_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->decimal('amount', 10, 2);
            $table->timestamp('created_at')->nullable();

            $table->unique(['refund_id', 'allocation_id']);
        });

        if (Schema::hasColumn('refunds', 'allocation_id')) {
            DB::statement(
                'insert into refund_allocation (refund_id, allocation_id, amount, created_at)'
                    . ' select refund_id, allocation_id, amount, created_at from refunds'
            );

            Schema::table('refunds', function (Blueprint $table) {
                $table->dropForeign(['allocation_id']);
                $table->dropColumn('allocation_id');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('refunds', 'allocation_id')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->foreignId('allocation_id')
                    ->nullable()
                    ->constrained('payment_invoice_allocation', 'allocation_id')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });

            DB::statement(
                'update refunds r set allocation_id = ra.allocation_id'
                    . ' from refund_allocation ra where ra.refund_id = r.refund_id'
            );
        }

        Schema::dropIfExists('refund_allocation');
    }
};
