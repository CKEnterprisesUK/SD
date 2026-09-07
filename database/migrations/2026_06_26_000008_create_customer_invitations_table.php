<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->foreignId('customer_contact_id')
                ->nullable()
                ->constrained('customer_contacts')
                ->nullOnDelete();

            $table->string('email');

            $table->foreignId('invited_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            $table->index(['customer_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_invitations');
    }
};
