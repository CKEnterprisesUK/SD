<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Contracts\View\View;

/**
 * Customer-facing "My Projects" surface.
 *
 * This route lives under `auth` (NOT the `admin.` group). A customer user sees
 * the projects belonging to their linked customer (via `customer_id`), and
 * opens each project through the shared, permission-filtered document library
 * (`projects.library`). Document access inside the library is gated per-folder
 * by the FolderPolicy / PermissionResolver.
 */
class ProjectController extends Controller
{
    /**
     * List the projects belonging to the authenticated customer.
     */
    public function index(): View
    {
        $user = auth()->user();

        abort_unless($user->isCustomer(), 403);
        abort_unless($user->customer_id !== null, 403);

        $projects = Project::query()
            ->where('customer_id', $user->customer_id)
            ->orderBy('name')
            ->paginate(15)
            ->through(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'reference' => $project->reference,
                'state' => $project->state,
            ]);

        return view('customer.projects.index', [
            'projects' => $projects,
        ]);
    }
}
