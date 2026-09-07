<?php

namespace App\Policies;

use App\Models\ProjectFolder;
use App\Models\User;
use App\Services\PermissionResolver;

/**
 * Authorization for folder viewing and management.
 *
 * Read access delegates to PermissionResolver::canRead. Per-project folder
 * management is admin-restricted (req 5.5), and structural writes are blocked
 * once the project is Complete (req 2.1/2.2/2.4) as defense in depth alongside
 * the EnsureProjectWritable middleware.
 */
class FolderPolicy
{
    public function __construct(private readonly PermissionResolver $resolver)
    {
    }

    /**
     * View a folder: any user who can read it.
     */
    public function view(User $user, ProjectFolder $folder): bool
    {
        return $this->resolver->canRead($user, $folder);
    }

    /**
     * Manage a folder (rename/reorder/delete/permissions): admin only and
     * only while the project is not Complete.
     */
    public function manage(User $user, ProjectFolder $folder): bool
    {
        return $user->isAdmin() && ! $folder->project->isComplete();
    }

    /**
     * Create a subfolder: requires write access and a non-Complete project.
     */
    public function createSubfolder(User $user, ProjectFolder $folder): bool
    {
        return $this->resolver->canWrite($user, $folder)
            && ! $folder->project->isComplete();
    }
}
