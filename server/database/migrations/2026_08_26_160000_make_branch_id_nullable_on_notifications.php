<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Raw ALTER rather than doctrine/dbal, which this project does not
        // install. Guarded so a fresh database — where the create migration
        // already declares it nullable — is a no-op.
        if (Schema::hasColumn('notifications', 'branch_id')) {
            DB::statement('ALTER TABLE notifications ALTER COLUMN branch_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifications', 'branch_id')) {
            DB::statement('ALTER TABLE notifications ALTER COLUMN branch_id SET NOT NULL');
        }
    }
};
