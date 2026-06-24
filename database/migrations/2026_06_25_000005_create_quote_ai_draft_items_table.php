<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_ai_draft_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_ai_draft_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pricing_rate_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->default('other_works');
            $table->string('rate_item_code')->nullable();
            $table->string('clean_customer_description');
            $table->longText('internal_reasoning')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->default('item');
            $table->unsignedInteger('base_unit_cost_pence')->default(0);
            $table->unsignedInteger('base_total_pence')->default(0);
            $table->decimal('markup_percent', 6, 2)->default(0);
            $table->decimal('contingency_percent', 6, 2)->default(0);
            $table->decimal('vat_percent', 6, 2)->default(0);
            $table->unsignedInteger('contingency_pence')->default(0);
            $table->unsignedInteger('markup_pence')->default(0);
            $table->unsignedInteger('subtotal_pence')->default(0);
            $table->unsignedInteger('vat_pence')->default(0);
            $table->unsignedInteger('total_pence')->default(0);
            $table->string('confidence')->default('medium');
            $table->string('pricing_source')->default('rate_card');
            $table->longText('evidence')->nullable();
            $table->longText('warnings')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['quote_id', 'status']);
            $table->index(['quote_ai_draft_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_ai_draft_items');
    }
};
