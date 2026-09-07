<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folder_permissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_folder_id')
                ->constrained('project_folders')
                ->cascadeOnDelete();

            $table->string('role');   // admin|contractor|customer
            $table->string('level');  // read-write|read-only|no-access

            $table->timestamps();

            $table->unique(['project_folder_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folder_permissions');
    }
};
