<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FolderTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sort_order',
        'subfolders',
        'permissions',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'subfolders' => 'array',
        'permissions' => 'array',
    ];

    /**
     * The default master folder template: five ordered top-level folders, each
     * with its default subfolders and per-role permission levels. Used by
     * FolderTemplateSeeder to initialise the editable master template.
     */
    public const DEFAULT_TEMPLATE = [
        [
            'name' => 'Planning and Design docs',
            'subfolders' => ['Drawings', 'Plans', 'Specifications'],
            'permissions' => ['admin' => 'read-write', 'contractor' => 'read-only', 'customer' => 'read-only'],
        ],
        [
            'name' => 'Quotes',
            'subfolders' => ['Issued', 'Accepted'],
            'permissions' => ['admin' => 'read-write', 'contractor' => 'no-access', 'customer' => 'read-only'],
        ],
        [
            'name' => 'Build Stage',
            'subfolders' => ['Progress', 'Photos', 'Certificates'],
            'permissions' => ['admin' => 'read-write', 'contractor' => 'read-only', 'customer' => 'read-only'],
        ],
        [
            'name' => 'Health and Safety',
            'subfolders' => ['Risk Assessments', 'Method Statements'],
            'permissions' => ['admin' => 'read-write', 'contractor' => 'read-only', 'customer' => 'read-only'],
        ],
        [
            'name' => 'SiteDesk Admin Only',
            'subfolders' => ['Internal'],
            'permissions' => ['admin' => 'read-write', 'contractor' => 'no-access', 'customer' => 'no-access'],
        ],
    ];
}
