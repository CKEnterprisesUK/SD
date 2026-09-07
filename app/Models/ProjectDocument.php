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
        'uploaded_by_user_id',
        'original_name',
        'storage_path',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(ProjectFolder::class, 'project_folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
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
