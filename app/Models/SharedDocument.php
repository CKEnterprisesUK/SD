<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A canonical document stored ONCE on the private disk and referenced into a
 * chosen master-template folder of every newly created project.
 *
 * The bytes are never duplicated per project: each project's copy is a
 * ProjectDocument row whose shared_document_id points here. When is_locked is
 * true, those reference rows cannot be deleted from a project's library — the
 * document is managed centrally on the folder-template settings page.
 *
 * Like ProjectDocument, this model intentionally exposes NO URL accessor: the
 * file lives only on the private `local` disk and is served through the
 * authenticated DocumentServeController streaming route via its references.
 */
class SharedDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_template_id',
        'folder_template_name',
        'uploaded_by_user_id',
        'original_name',
        'storage_path',
        'mime_type',
        'size_bytes',
        'is_locked',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'is_locked' => 'boolean',
    ];

    public function folderTemplate(): BelongsTo
    {
        return $this->belongsTo(FolderTemplate::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * The per-project reference rows that point at this canonical document.
     */
    public function references(): HasMany
    {
        return $this->hasMany(ProjectDocument::class, 'shared_document_id');
    }
}
