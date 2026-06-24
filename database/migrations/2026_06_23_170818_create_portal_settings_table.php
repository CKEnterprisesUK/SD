<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_settings', function (Blueprint $table) {
            $table->id();

            $table->string('portal_name')->default('SiteDesk');
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_number')->nullable();
            $table->string('vat_number')->nullable();

            $table->string('logo_path')->nullable();
            $table->string('primary_colour')->default('#1d70b8');

            $table->string('accounts_email')->nullable();
            $table->unsignedInteger('payment_terms_days')->default(7);

            $table->text('invoice_wording')->nullable();
            $table->text('pdf_footer')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_settings');
    }
};