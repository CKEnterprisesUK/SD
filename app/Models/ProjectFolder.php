<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectFolder extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFolderFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'parent_id',
        'name',
        'is_top_level',
        'sort_order',
    ];

    protected $casts = [
        'is_top_level' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(FolderPermission::class);
    }

    /**
     * Walk up to the top-level ancestor that carries the permission rows.
     */
    public function topLevelFolder(): self
    {
        $node = $this;

        while (! $node->is_top_level && $node->parent) {
            $node = $node->parent;
        }

        return $node;
    }
}
