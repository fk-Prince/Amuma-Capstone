<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_cases', function (Blueprint $table) {
            $table->id('diagnosis_case_id');
            $table->uuid('uuid')->unique();
            $table->foreignId('branch_id')
                ->constrained('branches', 'branch_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX diagnosis_cases_branch_title_unique ON diagnosis_cases (branch_id, LOWER(title))');
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_cases');
    }
};
