<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_charge_diagnosis', function (Blueprint $table) {
            $table->id('admission_charge_diagnosis_id');
            $table->foreignId('admission_period_id')
                ->constrained('admission_periods', 'admission_period_id')
                ->cascadeOnDelete();
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('invoices', 'invoice_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_charge_diagnosis');
    }
};
