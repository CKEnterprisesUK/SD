<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('action');       // uploaded|downloaded|deleted|copied|folder_created|folder_renamed|folder_reordered|folder_deleted
            $table->string('target_type');  // document|folder

            $table->foreignId('project_document_id')
                ->nullable()
                ->constrained('project_documents')
                ->nullOnDelete();

            $table->foreignId('project_folder_id')
                ->nullable()
                ->constrained('project_folders')
                ->nullOnDelete();

            $table->json('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_audit_logs');
    }
};
