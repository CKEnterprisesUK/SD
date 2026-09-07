<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderPermission extends Model
{
    /** @use HasFactory<\Database\Factories\FolderPermissionFactory> */
    use HasFactory;

    protected $fillable = [
        'project_folder_id',
        'role',
        'level',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(ProjectFolder::class, 'project_folder_id');
    }
}
