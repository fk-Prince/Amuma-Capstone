<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_accesses', function (Blueprint $table) {
            $table->id('patient_access_id');
            $table->foreignId('client_id')
                ->constrained('clients', 'client_id');

            $table->foreignId('patient_id')
                ->constrained('patients', 'patient_id');
            $table->boolean('have_access')
                ->default(false);
            $table->string('relationship_type')
                ->default('relative');
            $table->unique(['client_id', 'patient_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_accesses');
    }
};
