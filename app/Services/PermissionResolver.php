<?php

namespace App\Services;

use App\Models\FolderPermission;
use App\Models\Project;
use App\Models\ProjectFolder;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves a user's effective access level for a project folder.
 *
 * Effective access is one of: read-write | read-only | no-access.
 *
 * Resolution rules (see design.md "Permission Resolution"):
 *  - Admin users always resolve to read-write.
 *  - Non-admin users must first pass a project-scope check:
 *      * customer   -> project.customer_id === user.customer_id
 *      * contractor -> the user's Contractor is assigned via project_contractors
 *    A user outside scope resolves to no-access.
 *  - In-scope non-admin users resolve through the folder's top-level ancestor
 *    (folder.topLevelFolder()) and the FolderPermission for their role; a
 *    missing permission row resolves to no-access.
 *
 * Because subfolders resolve via topLevelFolder(), a subfolder's effective
 * level always equals its top-level ancestor's level.
 */
class PermissionResolver
{
    public const READ_WRITE = 'read-write';
    public const READ_ONLY = 'read-only';
    public const NO_ACCESS = 'no-access';

    /**
     * Resolve the effective access level for a user on a folder.
     */
    public function level(User $user, ProjectFolder $folder): string
    {
        if ($user->isAdmin()) {
            return self::READ_WRITE;
        }

        if (! $this->inProjectScope($user, $folder->project)) {
            return self::NO_ACCESS;
        }

        $role = $this->roleFor($user);

        if ($role === null) {
            return self::NO_ACCESS;
        }

        $topLevel = $folder->topLevelFolder();

        $permission = FolderPermission::query()
            ->where('project_folder_id', $topLevel->id)
            ->where('role', $role)
            ->first();

        return $permission?->level ?? self::NO_ACCESS;
    }

    /**
     * Whether the user can read the folder (level !== no-access).
     */
    public function canRead(User $user, ProjectFolder $folder): bool
    {
        return $this->level($user, $folder) !== self::NO_ACCESS;
    }

    /**
     * Whether the user can write to the folder (level === read-write).
     */
    public function canWrite(User $user, ProjectFolder $folder): bool
    {
        return $this->level($user, $folder) === self::READ_WRITE;
    }

    /**
     * Top-level folders of the project whose resolved level !== no-access.
     *
     * A no-access top-level folder hides its whole subtree from browsing.
     *
     * @return Collection<int, ProjectFolder>
     */
    public function visibleTopLevelFolders(User $user, Project $project): Collection
    {
        return $project->topLevelFolders()
            ->get()
            ->filter(fn (ProjectFolder $folder) => $this->canRead($user, $folder))
            ->values();
    }

    /**
     * Determine whether the user's scope allows access to the given project.
     */
    private function inProjectScope(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isCustomer()) {
            return $user->customer_id !== null
                && $user->customer_id === $project->customer_id;
        }

        if ($user->isContractor()) {
            $contractor = $user->contractor;

            if ($contractor === null) {
                return false;
            }

            return $project->contractors()
                ->where('contractors.id', $contractor->id)
                ->exists();
        }

        return false;
    }

    /**
     * Map a user to the FolderPermission role string, or null if unmapped.
     */
    private function roleFor(User $user): ?string
    {
        if ($user->isCustomer()) {
            return 'customer';
        }

        if ($user->isContractor()) {
            return 'contractor';
        }

        return null;
    }
}
