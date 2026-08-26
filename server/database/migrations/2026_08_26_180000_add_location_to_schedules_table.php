<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('schedules', 'location_id')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->foreignId('location_id')
                    ->nullable()
                    ->after('patient_id')
                    ->constrained('locations', 'location_id');
            });
        }

        // Backfill homecare schedules from the patient's location, which is
        // where the homecare service address was stored until now. Facility
        // schedules stay null — those are delivered on-site at the branch.
        DB::statement("
            UPDATE schedules
            SET location_id = patients.location_id
            FROM patients
            WHERE schedules.patient_id = patients.patient_id
              AND schedules.category = 'Homecare'
              AND schedules.location_id IS NULL
              AND patients.location_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('schedules', 'location_id')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->dropConstrainedForeignId('location_id');
            });
        }
    }
};
