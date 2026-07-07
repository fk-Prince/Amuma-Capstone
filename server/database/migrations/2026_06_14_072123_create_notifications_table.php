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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->unsignedBigInteger('to_user_id');
            $table->unsignedBigInteger('from_user_id');
            $table->foreignId('branch_id')->constrained('branches', 'branch_id');
            $table->string('message_type');
            $table->text('message');
            $table->boolean('has_read')->default(false);
            $table->timestamps();


            $table->foreign('to_user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('from_user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
