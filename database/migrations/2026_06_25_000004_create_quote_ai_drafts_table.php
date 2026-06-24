<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_ai_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_ai_generation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending_review');
            $table->string('detected_job_type')->nullable();
            $table->string('selected_template_code')->nullable();
            $table->string('overall_confidence')->default('medium');
            $table->longText('pricing_basis')->nullable();
            $table->longText('missing_information')->nullable();
            $table->longText('warnings')->nullable();
            $table->longText('assumptions')->nullable();
            $table->longText('exclusions')->nullable();
            $table->longText('internal_reasoning')->nullable();
            $table->longText('customer_message')->nullable();
            $table->longText('scope_of_works')->nullable();
            $table->longText('timeline')->nullable();
            $table->longText('terms')->nullable();
            $table->timestamps();

            $table->index(['quote_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_ai_drafts');
    }
};
