<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractor_invoices', function (Blueprint $table) {
            $table->decimal('monday_days', 3, 2)->default(0)->after('day_rate_overridden');
            $table->decimal('tuesday_days', 3, 2)->default(0)->after('monday_days');
            $table->decimal('wednesday_days', 3, 2)->default(0)->after('tuesday_days');
            $table->decimal('thursday_days', 3, 2)->default(0)->after('wednesday_days');
            $table->decimal('friday_days', 3, 2)->default(0)->after('thursday_days');
            $table->decimal('saturday_days', 3, 2)->default(0)->after('friday_days');
            $table->decimal('sunday_days', 3, 2)->default(0)->after('saturday_days');
        });
    }

    public function down(): void
    {
        Schema::table('contractor_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'monday_days',
                'tuesday_days',
                'wednesday_days',
                'thursday_days',
                'friday_days',
                'saturday_days',
                'sunday_days',
            ]);
        });
    }
};