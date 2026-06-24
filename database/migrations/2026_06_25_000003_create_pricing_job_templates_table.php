<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_job_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->default('general');
            $table->text('description')->nullable();
            $table->longText('typical_scope')->nullable();
            $table->longText('typical_labour')->nullable();
            $table->longText('typical_materials')->nullable();
            $table->longText('typical_plant')->nullable();
            $table->longText('typical_waste')->nullable();
            $table->longText('default_assumptions')->nullable();
            $table->longText('default_exclusions')->nullable();
            $table->longText('risk_notes')->nullable();
            $table->longText('required_information')->nullable();
            $table->longText('suggested_rate_item_codes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_job_templates');
    }
};
