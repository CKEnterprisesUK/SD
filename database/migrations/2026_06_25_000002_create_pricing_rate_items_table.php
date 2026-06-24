<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_rate_card_id')->constrained()->cascadeOnDelete();
            $table->string('category')->default('other_works');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('customer_description')->nullable();
            $table->string('unit')->default('item');
            $table->unsignedInteger('base_cost_pence')->default(0);
            $table->decimal('default_markup_percent', 6, 2)->nullable();
            $table->decimal('vat_percent', 6, 2)->nullable();
            $table->text('aliases')->nullable();
            $table->longText('quantity_rules')->nullable();
            $table->longText('internal_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['pricing_rate_card_id', 'category']);
            $table->index(['pricing_rate_card_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rate_items');
    }
};
