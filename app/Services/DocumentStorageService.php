<?php

namespace App\Services;

use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Centralizes all document byte operations against the private `local` disk
 * (storage/app/private). Documents are stored under a deterministic,
 * non-guessable key: `projects/{project_id}/{folder_id}/{ulid}.{ext}`, and are
 * never written to the public disk — the ProjectDocument model exposes no URL
 * accessor, so no public/asset() URL can ever be produced for a document.
 *
 * Every mutating operation (store/copy/delete) records an audit entry via
 * AuditLogger so the document history is always captured.
 *
 * See design: "Storage disk" and "Secure Document Serving"
 * (requirements 7.1, 8.1, 9.2, 9.3).
 */
class DocumentStorageService
{
    /**
     * The private disk name. Configured in config/filesystems.php with root
     * storage_path('app/private') — never publicly served directly.
     */
    protected const DISK = 'local';

    /**
     * Store an uploaded file on the private disk and create its ProjectDocument.
     *
     * The file is written under `projects/{project_id}/{folder_id}/{ulid}.{ext}`
     * and a folder-linked ProjectDocument record is created capturing the
     * original name, storage key, mime type, size, and uploading user.
     */
    public function store(UploadedFile $file, ProjectFolder $folder): ProjectDocument
    {
        $key = $this->buildKey($folder, $file->getClientOriginalExtension());

        $this->disk()->putFileAs(
            dirname($key),
            $file,
            basename($key)
        );

        $document = ProjectDocument::create([
            'project_folder_id' => $folder->getKey(),
            'uploaded_by_user_id' => auth()->id(),
            'original_name' => $file->getClientOriginalName(),
            'storage_path' => $key,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize() ?? 0,
        ]);

        AuditLogger::documentUploaded($document);

        return $document;
    }

    /**
     * Duplicate a document's private file and record into a destination folder.
     *
     * The original document is left completely unchanged; a brand-new file is
     * written under the destination folder's key space with identical content
     * and original_name, and a new ProjectDocument record is created for it.
     */
    public function copy(ProjectDocument $document, ProjectFolder $destination): ProjectDocument
    {
        $extension = pathinfo($document->storage_path, PATHINFO_EXTENSION);

        $newKey = $this->buildKey($destination, $extension);

        $this->disk()->copy($document->storage_path, $newKey);

        $copy = ProjectDocument::create([
            'project_folder_id' => $destination->getKey(),
            'uploaded_by_user_id' => auth()->id(),
            'original_name' => $document->original_name,
            'storage_path' => $newKey,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
        ]);

        AuditLogger::documentCopied($copy, [
            'source_document_id' => $document->getKey(),
        ]);

        return $copy;
    }

    /**
     * Remove a document's private file and its database record.
     *
     * The audit entry is recorded BEFORE the record is deleted so the target
     * document id and its details are captured while they still exist.
     *
     * Locked documents (shared, undeletable references) are never removed here:
     * they are managed centrally on the folder-template settings page. A
     * shared reference that is somehow unlocked deletes only its reference row
     * — never the canonical file, which other projects still reference.
     */
    public function delete(ProjectDocument $document): void
    {
        // A locked document cannot be deleted from a project library at all.
        if ($document->isLocked()) {
            throw new \RuntimeException('This document is locked and cannot be deleted.');
        }

        AuditLogger::documentDeleted($document);

        // A shared reference owns no bytes of its own; only drop the row so the
        // canonical file remains available to other projects.
        if (! $document->isSharedReference()) {
            $this->disk()->delete($document->storage_path);
        }

        $document->delete();
    }

    /**
     * Build a deterministic, non-guessable storage key for a folder.
     *
     * Format: `projects/{project_id}/{folder_id}/{ulid}.{ext}`.
     */
    protected function buildKey(ProjectFolder $folder, ?string $extension): string
    {
        $filename = (string) Str::ulid();

        if (! empty($extension)) {
            $filename .= '.'.$extension;
        }

        return sprintf(
            'projects/%s/%s/%s',
            $folder->project_id,
            $folder->getKey(),
            $filename
        );
    }

    /**
     * The private disk instance used for all document byte operations.
     */
    protected function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(self::DISK);
    }
}
