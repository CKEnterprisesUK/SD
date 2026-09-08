<?php

namespace App\Services;

use App\Models\DocumentAuditLog;
use App\Models\ProjectDocument;
use App\Models\ProjectFolder;

/**
 * Central helper for recording document/folder activity to `document_audit_logs`.
 *
 * Every entry captures the acting user, the action, the target document/folder,
 * the containing project, an optional metadata payload, and a timestamp.
 *
 * The DocumentAuditLog model has `$timestamps = false` with `created_at` fillable,
 * so `created_at` is set explicitly here.
 */
class AuditLogger
{
    // --- Document operations -------------------------------------------------

    public static function documentUploaded(ProjectDocument $document, array $metadata = []): DocumentAuditLog
    {
        return self::recordDocument('uploaded', $document, $metadata);
    }

    public static function documentDownloaded(ProjectDocument $document, array $metadata = []): DocumentAuditLog
    {
        return self::recordDocument('downloaded', $document, $metadata);
    }

    public static function documentDeleted(ProjectDocument $document, array $metadata = []): DocumentAuditLog
    {
        return self::recordDocument('deleted', $document, $metadata);
    }

    public static function documentCopied(ProjectDocument $document, array $metadata = []): DocumentAuditLog
    {
        return self::recordDocument('copied', $document, $metadata);
    }

    public static function documentMoved(ProjectDocument $document, array $metadata = []): DocumentAuditLog
    {
        return self::recordDocument('moved', $document, $metadata);
    }

    public static function documentRenamed(ProjectDocument $document, array $metadata = []): DocumentAuditLog
    {
        return self::recordDocument('renamed', $document, $metadata);
    }

    // --- Folder operations ---------------------------------------------------

    public static function folderCreated(ProjectFolder $folder, array $metadata = []): DocumentAuditLog
    {
        return self::recordFolder('folder_created', $folder, $metadata);
    }

    public static function folderRenamed(ProjectFolder $folder, array $metadata = []): DocumentAuditLog
    {
        return self::recordFolder('folder_renamed', $folder, $metadata);
    }

    public static function folderReordered(ProjectFolder $folder, array $metadata = []): DocumentAuditLog
    {
        return self::recordFolder('folder_reordered', $folder, $metadata);
    }

    public static function folderMoved(ProjectFolder $folder, array $metadata = []): DocumentAuditLog
    {
        return self::recordFolder('folder_moved', $folder, $metadata);
    }

    public static function folderDeleted(ProjectFolder $folder, array $metadata = []): DocumentAuditLog
    {
        return self::recordFolder('folder_deleted', $folder, $metadata);
    }

    // --- Internals -----------------------------------------------------------

    /**
     * Record an audit entry for a document target.
     */
    protected static function recordDocument(string $action, ProjectDocument $document, array $metadata = []): DocumentAuditLog
    {
        $folder = $document->folder;

        $projectId = $folder?->project_id;

        $metadata = array_merge([
            'original_name' => $document->original_name,
        ], $metadata);

        return self::write([
            'project_id' => $projectId,
            'action' => $action,
            'target_type' => 'document',
            'project_document_id' => $document->getKey(),
            'project_folder_id' => $document->project_folder_id,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Record an audit entry for a folder target.
     */
    protected static function recordFolder(string $action, ProjectFolder $folder, array $metadata = []): DocumentAuditLog
    {
        $metadata = array_merge([
            'name' => $folder->name,
        ], $metadata);

        return self::write([
            'project_id' => $folder->project_id,
            'action' => $action,
            'target_type' => 'folder',
            'project_folder_id' => $folder->getKey(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Persist a fully-built audit entry, filling in actor and timestamp.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected static function write(array $attributes): DocumentAuditLog
    {
        return DocumentAuditLog::create(array_merge([
            'user_id' => auth()->id(),
            'created_at' => now(),
        ], $attributes));
    }
}
