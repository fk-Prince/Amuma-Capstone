<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_periods', function (Blueprint $table) {
            $table->id('admission_period_id');
            $table->foreignId('patient_admission_id')
                ->constrained('patient_admissions', 'patient_admission_id');
            $table->foreignId('branch_contract_id')
                ->constrained('branch_contracts', 'branch_contract_id');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->enum('status', ['pending', 'active', 'inactive', 'cancelled'])
                ->default('active')
                ->index();
            $table->enum('reason', [
                'admitted',
                'extended',
                'accommodation_change',
            ]);
            $table->string('note')->nullable();
            $table->foreignId('parent_admission_period_id')
                ->nullable()
                ->constrained('admission_periods', 'admission_period_id');
            $table->timestamps();
            $table->index(['patient_admission_id', 'admission_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_periods');
    }
};
