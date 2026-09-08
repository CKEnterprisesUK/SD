<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDocument extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'project_folder_id',
        'shared_document_id',
        'is_locked',
        'uploaded_by_user_id',
        'original_name',
        'storage_path',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'is_locked' => 'boolean',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(ProjectFolder::class, 'project_folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * The canonical shared document this row references, if any. When set, this
     * ProjectDocument is a reference (no project-owned bytes of its own — the
     * canonical file lives on the SharedDocument's storage_path).
     */
    public function sharedDocument(): BelongsTo
    {
        return $this->belongsTo(SharedDocument::class, 'shared_document_id');
    }

    /**
     * Whether this document is a reference to a canonical shared document
     * rather than a project-owned upload.
     */
    public function isSharedReference(): bool
    {
        return $this->shared_document_id !== null;
    }

    /**
     * Whether this document may not be deleted from the project library.
     * Locked documents (shared, undeletable references) are managed centrally.
     */
    public function isLocked(): bool
    {
        return (bool) $this->is_locked;
    }

    /**
     * The private-disk key that holds this document's bytes. For a shared
     * reference this is the canonical file; otherwise the row's own upload.
     */
    public function resolvedStoragePath(): ?string
    {
        if ($this->isSharedReference()) {
            return $this->sharedDocument?->storage_path;
        }

        return $this->storage_path;
    }

    /*
     * NOTE: ProjectDocument intentionally exposes NO URL accessor.
     *
     * Documents live only on the private `local` disk (storage/app/private) and
     * are served exclusively through the authenticated, permission-checked
     * DocumentServeController streaming route. Unlike QuoteFile, this model must
     * never define getUrlAttribute()/asset() so no public URL can be produced
     * for a document (see design: Secure Document Serving, requirements 8.2/8.6).
     */
}
