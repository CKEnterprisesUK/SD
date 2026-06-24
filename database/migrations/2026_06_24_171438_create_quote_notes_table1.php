<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quote_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('type')->default('general');
            $table->string('room_or_area')->nullable();
            $table->text('body');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['quote_id', 'created_at']);
            $table->index(['quote_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_notes');
    }
};