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
        // `payments` is created later in the sequence, so the foreign key is
        // added by its own migration once that table exists.
        Schema::create('refunds', function (Blueprint $table) {
            $table->id('refund_id');
            $table->unsignedBigInteger('payment_id');
            $table->decimal('amount', 10, 2);
            $table->string('refund_method', 50);
            $table->string('reference_id')->nullable();
            $table->string('masked_card_number', 25)->nullable();
            $table->string('status', 50);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index('reference_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
