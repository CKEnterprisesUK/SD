<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A "shared document" is a canonical file stored ONCE on the private disk
     * and referenced into a chosen template folder of every newly created
     * project. The bytes are never duplicated per project — each project's copy
     * is a ProjectDocument reference row pointing at the shared_document_id.
     */
    public function up(): void
    {
        Schema::create('shared_documents', function (Blueprint $table) {
            $table->id();

            // The master-template folder this document is attached to. Matched
            // by NAME at seed time (template rows are wiped/recreated on every
            // save), so we also store the folder name for resilience.
            $table->foreignId('folder_template_id')
                ->nullable()
                ->constrained('folder_templates')
                ->nullOnDelete();

            $table->string('folder_template_name');

            $table->foreignId('uploaded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('original_name');
            $table->string('storage_path'); // private disk key, never a URL
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);

            // When locked, the referenced copies cannot be deleted from a
            // customer/contractor/admin folder view.
            $table->boolean('is_locked')->default(true);

            $table->timestamps();

            $table->index('folder_template_name');
        });

        Schema::table('project_documents', function (Blueprint $table) {
            // Non-null => this ProjectDocument is a reference to a shared
            // document, not a project-owned upload. The canonical bytes live
            // under the shared document's storage_path.
            $table->foreignId('shared_document_id')
                ->nullable()
                ->after('project_folder_id')
                ->constrained('shared_documents')
                ->cascadeOnDelete();

            // Locked references cannot be deleted from the library.
            $table->boolean('is_locked')
                ->default(false)
                ->after('shared_document_id');

            $table->index('shared_document_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shared_document_id');
            $table->dropColumn('is_locked');
        });

        Schema::dropIfExists('shared_documents');
    }
};
