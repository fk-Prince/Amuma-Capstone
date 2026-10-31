<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('message_reactions')) {
            return;
        }

        Schema::create('message_reactions', function (Blueprint $table) {
            // NOTE: this primary key name must match $primaryKey in MessageReaction.php.
            // If the model has no $primaryKey line, change this to $table->id();
            $table->id('reaction_id');
            $table->unsignedBigInteger('message_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('emoji', 32);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Intentionally empty so a rollback never drops chat data.
    }
};