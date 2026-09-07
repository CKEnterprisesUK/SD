<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAuditLog extends Model
{
    use HasFactory;

    /**
     * The table only carries a `created_at` column (useCurrent), no `updated_at`.
     * Disable Eloquent's automatic timestamp management and treat `created_at`
     * as a plain, database-defaulted datetime.
     */
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'user_id',
        'action',
        'target_type',
        'project_document_id',
        'project_folder_id',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ProjectDocument::class, 'project_document_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(ProjectFolder::class, 'project_folder_id');
    }
}
