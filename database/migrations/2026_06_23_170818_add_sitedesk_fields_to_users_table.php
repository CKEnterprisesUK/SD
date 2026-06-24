<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('contractor')->after('password');
            $table->string('status')->default('active')->after('role');
            $table->timestamp('last_login_at')->nullable()->after('status');

            $table->index(['role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'status']);

            $table->dropColumn([
                'role',
                'status',
                'last_login_at',
            ]);
        });
    }
};