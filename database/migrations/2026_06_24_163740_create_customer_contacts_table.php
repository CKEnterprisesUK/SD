<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('role')->nullable();

            $table->boolean('is_primary')->default(false);
            $table->boolean('receives_quotes')->default(true);
            $table->boolean('receives_invoices')->default(false);
            $table->boolean('portal_access_enabled')->default(false);

            $table->timestamps();

            $table->index(['customer_id', 'is_primary']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_contacts');
    }
};