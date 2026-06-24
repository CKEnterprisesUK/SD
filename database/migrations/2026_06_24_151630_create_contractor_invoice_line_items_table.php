<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractor_invoice_line_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contractor_invoice_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type')->default('other');
            $table->string('description');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->unsignedInteger('unit_amount_pence')->default(0);
            $table->unsignedInteger('total_pence')->default(0);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['contractor_invoice_id', 'sort_order'], 'cii_invoice_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_invoice_line_items');
    }
};