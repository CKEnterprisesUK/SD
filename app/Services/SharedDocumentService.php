<?php

namespace App\Services;

use App\Models\FolderTemplate;
use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use App\Models\SharedDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Manages canonical "shared documents" that are stored ONCE on the private disk
 * and referenced (not copied) into a chosen master-template folder of every
 * newly created project.
 *
 * Bytes live under `shared/{ulid}.{ext}` on the private `local` disk. Each
 * project's copy is a ProjectDocument reference row (shared_document_id set),
 * so a certificate uploaded once appears in every project's matching folder
 * without ever duplicating the file.
 */
class SharedDocumentService
{
    protected const DISK = 'local';

    /**
     * Store an uploaded file once and attach it to a master-template folder.
     */
    public function store(UploadedFile $file, FolderTemplate $template, bool $locked = true): SharedDocument
    {
        $key = $this->buildKey($file->getClientOriginalExtension());

        $this->disk()->putFileAs(
            dirname($key),
            $file,
            basename($key)
        );

        return SharedDocument::create([
            'folder_template_id' => $template->getKey(),
            'folder_template_name' => $template->name,
            'uploaded_by_user_id' => auth()->id(),
            'original_name' => $file->getClientOriginalName(),
            'storage_path' => $key,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize() ?? 0,
            'is_locked' => $locked,
        ]);
    }

    /**
     * Permanently remove a shared document: delete every project reference row,
     * then the canonical file and the shared_documents record.
     *
     * This is the ONLY path that removes a locked shared document; individual
     * project libraries cannot delete their locked references.
     */
    public function delete(SharedDocument $document): void
    {
        DB::transaction(function () use ($document) {
            // Remove the reference rows first (they carry no bytes of their own).
            $document->references()->delete();

            $this->disk()->delete($document->storage_path);

            $document->delete();
        });
    }

    /**
     * Create a ProjectDocument reference to a shared document inside the given
     * project folder. No bytes are written — the canonical file is reused.
     */
    public function reference(SharedDocument $shared, ProjectFolder $folder): ProjectDocument
    {
        return ProjectDocument::create([
            'project_folder_id' => $folder->getKey(),
            'shared_document_id' => $shared->getKey(),
            'is_locked' => $shared->is_locked,
            'uploaded_by_user_id' => $shared->uploaded_by_user_id,
            'original_name' => $shared->original_name,
            // Reference rows keep the canonical key so a direct read still
            // resolves; resolvedStoragePath() prefers the shared file.
            'storage_path' => $shared->storage_path,
            'mime_type' => $shared->mime_type,
            'size_bytes' => $shared->size_bytes,
        ]);
    }

    /**
     * Build a non-guessable storage key for a canonical shared file.
     *
     * Format: `shared/{ulid}.{ext}`.
     */
    protected function buildKey(?string $extension): string
    {
        $filename = (string) Str::ulid();

        if (! empty($extension)) {
            $filename .= '.'.$extension;
        }

        return 'shared/'.$filename;
    }

    protected function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(self::DISK);
    }
}
