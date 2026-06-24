<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rate_cards', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Default rate card');
            $table->decimal('default_markup_percent', 6, 2)->default(25);
            $table->decimal('high_risk_markup_percent', 6, 2)->default(30);
            $table->decimal('contingency_percent', 6, 2)->default(10);
            $table->decimal('vat_percent', 6, 2)->default(20);
            $table->decimal('regional_adjustment_percent', 6, 2)->default(0);
            $table->decimal('preliminaries_percent', 6, 2)->default(5);
            $table->unsignedInteger('minimum_job_charge_pence')->default(25000);
            $table->boolean('block_quote_sending_if_high_risk_missing_info')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rate_cards');
    }
};
