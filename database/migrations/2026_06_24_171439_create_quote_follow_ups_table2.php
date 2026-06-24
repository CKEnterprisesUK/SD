<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_follow_ups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quote_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('due_at');
            $table->timestamp('completed_at')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['due_at', 'completed_at']);
            $table->index(['quote_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_follow_ups');
    }
};