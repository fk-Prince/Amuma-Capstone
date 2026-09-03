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
        Schema::create('patient_assessments', function (Blueprint $table) {
            $table->id('patient_assessment_id');
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')
                ->constrained('patients', 'patient_id')
                ->cascadeOnDelete();

            $table->string('diagnosis')->nullable();
            $table->date('diagnosis_date')->nullable();
            $table->text('diagnosis_notes')->nullable();
            $table->string('diagnosis_file')->nullable();

            $table->string('condition', 20)->default('ambulatory');
            $table->string('mental_state', 20)->default('alert');
            $table->string('affect', 20)->default('cheerful');
            $table->string('behavior', 30)->default('cooperative');
            $table->string('communication', 30)->default('Coherent & Logical');
            $table->string('speech', 20)->default('clear');

            $table->unsignedTinyInteger('bathing')->default(5);
            $table->unsignedTinyInteger('transferring')->default(5);
            $table->unsignedTinyInteger('toileting')->default(5);
            $table->unsignedTinyInteger('grooming')->default(5);
            $table->unsignedTinyInteger('eating')->default(5);
            $table->unsignedTinyInteger('locomotion')->default(5);
            $table->unsignedTinyInteger('dressing')->default(5);

            $table->timestamps();

            $table->index('patient_id');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('patient_assessments');
    }
};
