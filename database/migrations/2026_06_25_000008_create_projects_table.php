<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('name');
            $table->string('reference')->nullable();
            $table->string('state')->default('Draft'); // Draft|Planning|Active|Complete
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['customer_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
