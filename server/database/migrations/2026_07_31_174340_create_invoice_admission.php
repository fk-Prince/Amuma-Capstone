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
        Schema::create('invoice_admission', function (Blueprint $table) {
            $table->id('invoice_admission_id');
            $table->foreignId('admission_period_id')
                ->constrained('admission_periods', 'admission_period_id');
            $table->foreignId('invoice_id')
                ->constrained('invoices', 'invoice_id');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_admission');
    }
};
