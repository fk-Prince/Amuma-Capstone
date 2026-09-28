<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('additional_charges', function (Blueprint $table) {
            $table->id('additional_charge_id');
            $table->foreignId('patient_admission_id')
                ->constrained('patient_admissions', 'patient_admission_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('invoice_id')
                ->constrained('invoices', 'invoice_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->enum('type', ['medication', 'supplies', 'additional_charges']);
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_charges');
    }
};
