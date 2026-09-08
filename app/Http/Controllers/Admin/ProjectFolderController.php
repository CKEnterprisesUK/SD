<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FolderPermission;
use App\Models\Project;
use App\Models\ProjectFolder;
use App\Services\AuditLogger;
use App\Services\PermissionResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Per-project folder management (admin only).
 *
 * Handles creating top-level and sub folders, renaming, reordering, deleting,
 * and setting per-role permission levels on top-level folders. All routes are
 * wrapped by the `project.writable` middleware (EnsureProjectWritable) so that
 * modifying operations are rejected once a Project is Complete; authorization
 * is enforced through FolderPolicy (`manage`).
 *
 * Subfolders never carry their own FolderPermission rows: they inherit the
 * top-level ancestor's permission at resolution time (PermissionResolver
 * resolves via ProjectFolder::topLevelFolder()).
 *
 * Every operation records a DocumentAuditLog entry via AuditLogger.
 */
class ProjectFolderController extends Controller
{
    /**
     * Roles that carry a configurable permission level on a top-level folder.
     */
    private const ROLES = ['admin', 'contractor', 'customer'];

    /**
     * Valid permission levels.
     */
    private const LEVELS = [
        PermissionResolver::READ_WRITE,
        PermissionResolver::READ_ONLY,
        PermissionResolver::NO_ACCESS,
    ];

    /**
     * Create a top-level folder (parent_id null) or a subfolder (parent_id set).
     *
     * A top-level folder receives default FolderPermission rows (admin
     * read-write; contractor/customer no-access unless overridden by the
     * request). Subfolders receive no permission rows and inherit at resolution.
     */
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                Rule::exists('project_folders', 'id')->where('project_id', $project->id),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.admin' => ['sometimes', Rule::in(self::LEVELS)],
            'permissions.contractor' => ['sometimes', Rule::in(self::LEVELS)],
            'permissions.customer' => ['sometimes', Rule::in(self::LEVELS)],
        ]);

        $parent = null;

        if (! empty($validated['parent_id'])) {
            $parent = ProjectFolder::where('project_id', $project->id)
                ->findOrFail($validated['parent_id']);
        }

        // FolderPolicy::manage is admin-only + project-not-Complete. Authorize
        // against the parent when nesting, otherwise a stub top-level folder.
        $authTarget = $parent ?? (new ProjectFolder())->setRelation('project', $project);
        Gate::authorize('manage', $authTarget);

        $folder = DB::transaction(function () use ($project, $parent, $validated) {
            $isTopLevel = $parent === null;

            $sortOrder = ProjectFolder::where('project_id', $project->id)
                ->where('parent_id', $parent?->id)
                ->max('sort_order');
            $sortOrder = $sortOrder === null ? 0 : $sortOrder + 1;

            $folder = ProjectFolder::create([
                'project_id' => $project->id,
                'parent_id' => $parent?->id,
                'name' => $validated['name'],
                'is_top_level' => $isTopLevel,
                'sort_order' => $sortOrder,
            ]);

            if ($isTopLevel) {
                $requested = $validated['permissions'] ?? [];

                $defaults = [
                    'admin' => PermissionResolver::READ_WRITE,
                    'contractor' => PermissionResolver::NO_ACCESS,
                    'customer' => PermissionResolver::NO_ACCESS,
                ];

                foreach (self::ROLES as $role) {
                    FolderPermission::create([
                        'project_folder_id' => $folder->id,
                        'role' => $role,
                        'level' => $requested[$role] ?? $defaults[$role],
                    ]);
                }
            }

            return $folder;
        });

        AuditLogger::folderCreated($folder, [
            'is_top_level' => $folder->is_top_level,
            'parent_id' => $folder->parent_id,
        ]);

        return back()->with('status', 'Folder created successfully.');
    }

    /**
     * Rename a folder.
     */
    public function update(Request $request, Project $project, ProjectFolder $folder)
    {
        $this->ensureFolderInProject($project, $folder);

        Gate::authorize('manage', $folder);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $previousName = $folder->name;

        $folder->update(['name' => $validated['name']]);

        AuditLogger::folderRenamed($folder, [
            'previous_name' => $previousName,
        ]);

        return back()->with('status', 'Folder renamed successfully.');
    }

    /**
     * Reorder a set of sibling folders by assigning sort_order from the given
     * ordered list of folder ids.
     */
    public function reorder(Request $request, Project $project)
    {
        $validated = $request->validate([
            'folder_ids' => ['required', 'array', 'min:1'],
            'folder_ids.*' => [
                'integer',
                Rule::exists('project_folders', 'id')->where('project_id', $project->id),
            ],
        ]);

        $folders = ProjectFolder::where('project_id', $project->id)
            ->whereIn('id', $validated['folder_ids'])
            ->get()
            ->keyBy('id');

        if ($folders->count() !== count($validated['folder_ids'])) {
            throw ValidationException::withMessages([
                'folder_ids' => 'One or more folders do not belong to this project.',
            ]);
        }

        // Authorize once against any folder in the set (all share the project
        // and FolderPolicy::manage only checks admin + project state).
        Gate::authorize('manage', $folders->first());

        DB::transaction(function () use ($validated, $folders) {
            foreach (array_values($validated['folder_ids']) as $index => $folderId) {
                /** @var ProjectFolder $folder */
                $folder = $folders[$folderId];
                $folder->update(['sort_order' => $index]);

                AuditLogger::folderReordered($folder, [
                    'sort_order' => $index,
                ]);
            }
        });

        return back()->with('status', 'Folders reordered successfully.');
    }

    /**
     * Delete a folder (and, via cascade, its subtree, documents, and
     * permission rows).
     */
    public function destroy(Project $project, ProjectFolder $folder)
    {
        $this->ensureFolderInProject($project, $folder);

        Gate::authorize('manage', $folder);

        // Record the audit entry before deletion so the target data is intact.
        AuditLogger::folderDeleted($folder, [
            'is_top_level' => $folder->is_top_level,
            'parent_id' => $folder->parent_id,
        ]);

        $folder->delete();

        return back()->with('status', 'Folder deleted successfully.');
    }

    /**
     * Set the per-role permission levels on a top-level folder.
     *
     * Only top-level folders carry permission rows; subfolders inherit at
     * resolution time, so this is rejected for non-top-level folders.
     */
    public function permissionsUpdate(Request $request, Project $project, ProjectFolder $folder)
    {
        $this->ensureFolderInProject($project, $folder);

        Gate::authorize('manage', $folder);

        abort_unless($folder->is_top_level, 422, 'Permissions can only be set on top-level folders.');

        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.admin' => ['required', Rule::in(self::LEVELS)],
            'permissions.contractor' => ['required', Rule::in(self::LEVELS)],
            'permissions.customer' => ['required', Rule::in(self::LEVELS)],
        ]);

        DB::transaction(function () use ($folder, $validated) {
            foreach (self::ROLES as $role) {
                FolderPermission::updateOrCreate(
                    ['project_folder_id' => $folder->id, 'role' => $role],
                    ['level' => $validated['permissions'][$role]],
                );
            }
        });

        return back()->with('status', 'Folder permissions updated successfully.');
    }

    /**
     * Move a folder under a different parent (or to the top level).
     *
     * A destination of null makes the folder top-level; a destination folder id
     * nests it beneath that folder. Moving a folder into itself or any of its
     * own descendants is rejected (cycle guard).
     *
     * Permission model side effects (subfolders inherit their top-level
     * ancestor's permissions via PermissionResolver::topLevelFolder):
     *  - Moving TO top level: the folder becomes top-level and receives default
     *    FolderPermission rows if it has none yet.
     *  - Moving UNDER a parent: the folder becomes a subfolder; any permission
     *    rows it carried are removed since it now inherits from the ancestor.
     */
    public function move(Request $request, Project $project, ProjectFolder $folder)
    {
        $this->ensureFolderInProject($project, $folder);

        Gate::authorize('manage', $folder);

        $validated = $request->validate([
            'destination_folder_id' => [
                'nullable',
                Rule::exists('project_folders', 'id')->where('project_id', $project->id),
            ],
        ]);

        $destination = null;

        if (! empty($validated['destination_folder_id'])) {
            $destination = ProjectFolder::where('project_id', $project->id)
                ->findOrFail($validated['destination_folder_id']);
        }

        // A move must actually change the parent.
        if ($destination?->id === $folder->parent_id
            || ($destination === null && $folder->parent_id === null)) {
            return back()->with('status', 'Folder is already in that location.');
        }

        // Cycle guard: cannot move a folder into itself or its own subtree.
        if ($destination !== null
            && in_array($destination->id, $folder->selfAndDescendantIds(), true)) {
            throw ValidationException::withMessages([
                'destination_folder_id' => 'A folder cannot be moved into itself or one of its subfolders.',
            ]);
        }

        $previousParentId = $folder->parent_id;

        DB::transaction(function () use ($project, $folder, $destination) {
            $isTopLevel = $destination === null;

            $sortOrder = ProjectFolder::where('project_id', $project->id)
                ->where('parent_id', $destination?->id)
                ->max('sort_order');
            $sortOrder = $sortOrder === null ? 0 : $sortOrder + 1;

            $folder->update([
                'parent_id' => $destination?->id,
                'is_top_level' => $isTopLevel,
                'sort_order' => $sortOrder,
            ]);

            if ($isTopLevel) {
                // Newly top-level: seed default permission rows if absent.
                if ($folder->permissions()->count() === 0) {
                    $defaults = [
                        'admin' => PermissionResolver::READ_WRITE,
                        'contractor' => PermissionResolver::NO_ACCESS,
                        'customer' => PermissionResolver::NO_ACCESS,
                    ];

                    foreach (self::ROLES as $role) {
                        FolderPermission::create([
                            'project_folder_id' => $folder->id,
                            'role' => $role,
                            'level' => $defaults[$role],
                        ]);
                    }
                }
            } else {
                // Now a subfolder: it inherits from its top-level ancestor, so
                // any permission rows it used to carry are no longer meaningful.
                $folder->permissions()->delete();
            }
        });

        AuditLogger::folderMoved($folder, [
            'previous_parent_id' => $previousParentId,
            'parent_id' => $folder->parent_id,
        ]);

        return back()->with('status', 'Folder moved successfully.');
    }

    /**
     * Ensure the folder belongs to the given project (guards manual id tampering
     * when route-model binding is not scoped).
     */
    private function ensureFolderInProject(Project $project, ProjectFolder $folder): void
    {
        abort_unless($folder->project_id === $project->id, 404);
    }
}
