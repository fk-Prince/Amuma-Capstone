<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('admission_contracts', function (Blueprint $table) {
            $table->id('admission_contract_id');

            $table->foreignId('branch_contract_id')
                ->constrained('branch_contracts', 'branch_contract_id');

            $table->foreignId('patient_admission_id')
                ->constrained('patient_admissions', 'patient_admission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_contracts');
    }
};
