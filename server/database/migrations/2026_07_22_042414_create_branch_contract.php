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
        Schema::create('branch_contracts', function (Blueprint $table) {
            $table->id('branch_contract_id');
            $table->foreignId('branch_id')->constrained('branches', 'branch_id');
            $table->enum('category', ['Homecare', 'Facility',]);
            $table->enum('accommodation_type', ['ADL', 'VIP', 'COMMON',]);
            $table->decimal('price', 10, 2);
            $table->enum('billing_cycle', ['MONTHLY',  'YEARLY',   'HOURLY',]);
            $table->boolean('is_active')->default(true);
            $table->string('description', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('branch_contracts');
    }
};
