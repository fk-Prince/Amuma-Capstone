<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename the old column if it exists under the old name.
        if (Schema::hasColumn('messages', 'duration') && !Schema::hasColumn('messages', 'attachment_duration')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->renameColumn('duration', 'attachment_duration');
            });
        }

        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'reply_to_message_id')) {
                $table->unsignedBigInteger('reply_to_message_id')->nullable();
            }

            if (!Schema::hasColumn('messages', 'attachment_url')) {
                $table->text('attachment_url')->nullable();
            }

            if (!Schema::hasColumn('messages', 'attachment_name')) {
                $table->string('attachment_name')->nullable();
            }

            if (!Schema::hasColumn('messages', 'attachment_duration')) {
                $table->unsignedInteger('attachment_duration')->nullable();
            }

            if (!Schema::hasColumn('messages', 'is_forwarded')) {
                $table->boolean('is_forwarded')->default(false);
            }

            if (!Schema::hasColumn('messages', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable();
            }

            if (!Schema::hasColumn('messages', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }

            if (!Schema::hasColumn('messages', 'pinned_at')) {
                $table->timestamp('pinned_at')->nullable();
            }

            if (!Schema::hasColumn('messages', 'pinned_by_user_id')) {
                $table->unsignedBigInteger('pinned_by_user_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty so a rollback never drops chat data.
    }
};