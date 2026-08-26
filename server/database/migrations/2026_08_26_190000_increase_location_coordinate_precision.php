<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The columns were decimal(10,3), which rounds a coordinate to about
     * 110 metres — far too coarse to pin a homecare visit on a map. Widening
     * to 7 decimal places is lossless for anything already stored.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE locations ALTER COLUMN latitude TYPE numeric(10, 7)');
        DB::statement('ALTER TABLE locations ALTER COLUMN longitude TYPE numeric(10, 7)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE locations ALTER COLUMN latitude TYPE numeric(10, 3)');
        DB::statement('ALTER TABLE locations ALTER COLUMN longitude TYPE numeric(10, 3)');
    }
};
