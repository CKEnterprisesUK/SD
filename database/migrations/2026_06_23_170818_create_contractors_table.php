<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();

            $table->string('company_name')->nullable();

            $table->unsignedInteger('day_rate_pence')->default(0);

            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractors');
    }
};