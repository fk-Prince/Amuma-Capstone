<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->id('medication_id');
            $table->foreignId('patient_id')
                ->constrained('patients', 'patient_id')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('strength');
            $table->decimal('dosage_amount', 8, 2);
            $table->string('dosage_unit', 50);
            $table->string('route', 50);
            $table->text('instructions');
            $table->string('taken_for')->nullable();
            $table->string('duration', 20);
            $table->string('frequency', 20)->default('everyday');
            $table->enum('kind', ['Scheduled', 'PRN']);
            $table->json('times')->nullable();
            $table->date('start_date');
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
