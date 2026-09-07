<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'created_by_user_id',
        'name',
        'reference',
        'state',
        'description',
    ];

    public const STATES = ['Draft', 'Planning', 'Active', 'Complete'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(ProjectFolder::class);
    }

    public function topLevelFolders(): HasMany
    {
        return $this->hasMany(ProjectFolder::class)
            ->where('is_top_level', true)
            ->orderBy('sort_order');
    }

    public function documents(): HasManyThrough
    {
        return $this->hasManyThrough(ProjectDocument::class, ProjectFolder::class);
    }

    public function contractors(): BelongsToMany
    {
        return $this->belongsToMany(Contractor::class, 'project_contractors')
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(DocumentAuditLog::class);
    }

    public function isComplete(): bool
    {
        return $this->state === 'Complete';
    }
}
