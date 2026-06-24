<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractor_invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contractor_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('invoice_number')->unique();

            $table->date('invoice_date');
            $table->date('week_commencing');

            // Supplier snapshot: contractor details at time of submission
            $table->string('supplier_name');
            $table->string('supplier_email')->nullable();
            $table->string('supplier_phone')->nullable();
            $table->text('supplier_address')->nullable();

            // Customer snapshot: building firm details at time of submission
            $table->string('customer_name')->nullable();
            $table->text('customer_address')->nullable();

            // Rate snapshot
            $table->unsignedInteger('default_day_rate_pence');
            $table->unsignedInteger('actual_day_rate_pence');
            $table->boolean('day_rate_overridden')->default(false);

            // Worked days
            $table->boolean('worked_monday')->default(false);
            $table->boolean('worked_tuesday')->default(false);
            $table->boolean('worked_wednesday')->default(false);
            $table->boolean('worked_thursday')->default(false);
            $table->boolean('worked_friday')->default(false);
            $table->boolean('worked_saturday')->default(false);
            $table->boolean('worked_sunday')->default(false);

            $table->decimal('days_worked', 4, 1)->default(0);

            // Money
            $table->unsignedInteger('subtotal_pence')->default(0);
            $table->unsignedInteger('vat_pence')->default(0);
            $table->unsignedInteger('total_pence')->default(0);

            $table->text('contractor_notes')->nullable();

            // Submission proof
            $table->text('contractor_confirmation_text');
            $table->timestamp('submitted_at');
            $table->string('submitted_ip')->nullable();

            // Lifecycle
            $table->string('status')->default('submitted');
            $table->string('pdf_path')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['contractor_id', 'week_commencing']);
            $table->index(['status']);
            $table->index(['submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_invoices');
    }
};