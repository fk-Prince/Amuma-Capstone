<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cameras', function (Blueprint $table) {
            $table->id('camera_id');
            $table->uuid('uuid')->unique();
            $table->foreignId('branch_id')->constrained('branches', 'branch_id');
            $table->foreignId('room_id')->constrained('rooms', 'room_id');
            $table->string('username');
            $table->string('ip_address', 45);
            $table->text('password');
            $table->enum('visibility', ['private', 'public'])->default('private');
            $table->timestamps();
            $table->unique(['branch_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};
