<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_line_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quote_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('source')->default('manual');
            $table->string('type')->default('other');

            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->default('item');

            $table->unsignedInteger('unit_amount_pence')->default(0);
            $table->unsignedInteger('total_pence')->default(0);

            $table->boolean('is_optional')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['quote_id', 'sort_order']);
            $table->index(['quote_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_line_items');
    }
};