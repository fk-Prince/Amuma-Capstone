<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('pinned_at')->nullable()->after('read_at');

            $table->foreignId('pinned_by_user_id')
                ->nullable()
                ->after('pinned_at')
                ->constrained('users', 'user_id')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index(['conversation_id', 'pinned_at']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['conversation_id', 'pinned_at']);
            $table->dropForeign(['pinned_by_user_id']);
            $table->dropColumn(['pinned_at', 'pinned_by_user_id']);
        });
    }
};