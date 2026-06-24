<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('quote_number')->unique();
            $table->string('title');
            $table->string('status')->default('draft');

            $table->text('site_address')->nullable();
            $table->text('summary')->nullable();
            $table->text('internal_notes')->nullable();

            $table->longText('final_customer_message')->nullable();
            $table->longText('final_scope')->nullable();
            $table->longText('final_assumptions')->nullable();
            $table->longText('final_exclusions')->nullable();
            $table->longText('final_timeline')->nullable();
            $table->longText('final_terms')->nullable();

            $table->unsignedInteger('subtotal_pence')->default(0);
            $table->unsignedInteger('vat_pence')->default(0);
            $table->unsignedInteger('total_pence')->default(0);

            $table->date('valid_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['customer_id', 'status']);
            $table->index('valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};