<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_diagnosis', function (Blueprint $table) {
            $table->id('patient_diagnosis_id');
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')
                ->constrained('patients', 'patient_id')
                ->cascadeOnDelete();
            $table->string('diagnosis')->nullable();
            $table->date('diagnosis_date')->nullable();
            $table->text('diagnosis_notes')->nullable();
            $table->string('diagnosis_file')->nullable();
            $table->timestamps();
            $table->index('patient_id');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('patient_diagnosis');
    }
};
