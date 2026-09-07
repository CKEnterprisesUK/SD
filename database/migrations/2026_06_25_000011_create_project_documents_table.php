<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_folder_id')
                ->constrained('project_folders')
                ->cascadeOnDelete();

            $table->foreignId('uploaded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('original_name');
            $table->string('storage_path');   // private disk key, never a URL
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);

            $table->timestamps();

            $table->index('project_folder_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_documents');
    }
};
