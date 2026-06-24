<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractor_invoices', function (Blueprint $table) {
            $table->text('review_comment')->nullable()->after('contractor_notes');

            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->after('review_comment')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
            $table->timestamp('returned_at')->nullable()->after('reviewed_at');
            $table->timestamp('ready_for_payment_at')->nullable()->after('returned_at');
            $table->timestamp('resubmitted_at')->nullable()->after('ready_for_payment_at');

            $table->unsignedInteger('submission_version')->default(1)->after('resubmitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('contractor_invoices', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by_user_id']);

            $table->dropColumn([
                'review_comment',
                'reviewed_by_user_id',
                'reviewed_at',
                'returned_at',
                'ready_for_payment_at',
                'resubmitted_at',
                'submission_version',
            ]);
        });
    }
};