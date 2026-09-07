<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFolder;
use App\Services\PermissionResolver;
use Illuminate\Contracts\View\View;

/**
 * Permission-filtered browsing of a project's document library.
 *
 * These routes live under `auth` (NOT the `admin.` group) so they are
 * reachable by any authorized role — admin, the owning customer, and assigned
 * contractors. Access is gated per action:
 *
 *  - show(Project): authorized via ProjectPolicy@view (admin / owning customer
 *    / assigned contractor). The visible top-level tree is filtered through
 *    PermissionResolver::visibleTopLevelFolders, so a no-access top-level
 *    folder (and its whole subtree) is hidden from the customer/contractor.
 *  - folder(Project, ProjectFolder): authorized via FolderPolicy@view
 *    (PermissionResolver::canRead). A no-access folder yields a 403 (req 6.4);
 *    a folder that does not belong to the project yields a 404.
 *
 * No document bytes are produced here — downloads flow through
 * DocumentServeController (documents.serve).
 */
class ProjectLibraryController extends Controller
{
    /**
     * Show a project's permission-filtered top-level folder tree.
     *
     * Requirements: 6.1, 6.2, 6.3.
     */
    public function show(Project $project): View
    {
        // Admin, owning customer, or assigned contractor may view (req 6.1).
        $this->authorize('view', $project);

        // Filter to top-level folders the user can read; a no-access top-level
        // folder hides its entire subtree from browsing (req 6.1, 6.3).
        $folders = app(PermissionResolver::class)->visibleTopLevelFolders(
            auth()->user(),
            $project,
        );

        return view('portal.library', [
            'project' => $project,
            'folders' => $folders,
        ]);
    }

    /**
     * Show a single folder's subfolders and documents.
     *
     * Requirements: 6.2, 6.3, 6.4.
     */
    public function folder(Project $project, ProjectFolder $folder): View
    {
        // The folder must belong to the requested project (req 6.2); otherwise
        // the resource does not exist for this project.
        abort_unless($folder->project_id === $project->id, 404);

        // Deny a direct request to a no-access folder with a 403 (req 6.4).
        // FolderPolicy@view delegates to PermissionResolver::canRead.
        $this->authorize('view', $folder);

        return view('portal.folder', [
            'project' => $project,
            'folder' => $folder,
            'subfolders' => $folder->children,
            'documents' => $folder->documents,
        ]);
    }
}
