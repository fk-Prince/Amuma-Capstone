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
        Schema::create('invoice_facilities', function (Blueprint $table) {
            $table->id('invoice_facility_id');
            $table->foreignId('branch_contract_id')
                ->constrained('branch_contracts', 'branch_contract_id');
            $table->foreignId('patient_admission_id')
                ->constrained('patient_admissions', 'patient_admission_id');
            $table->foreignId('invoice_id')
                ->constrained('invoices', 'invoice_id');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_facilities');
    }
};
