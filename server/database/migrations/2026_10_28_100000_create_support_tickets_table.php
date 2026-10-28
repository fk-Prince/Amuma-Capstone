<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id('support_ticket_id');
            $table->uuid('uuid')->unique();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches', 'branch_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('user_id')->constrained('users', 'user_id');

            $table->enum('category', ['billing', 'verification', 'technical', 'account', 'other']);
            $table->string('subject');
            $table->enum('status', ['open', 'answered', 'resolved'])->default('open');
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });

        Schema::create('support_ticket_replies', function (Blueprint $table) {
            $table->id('support_ticket_reply_id');

            $table->foreignId('support_ticket_id')
                ->constrained('support_tickets', 'support_ticket_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->text('message');
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_replies');
        Schema::dropIfExists('support_tickets');
    }
};
