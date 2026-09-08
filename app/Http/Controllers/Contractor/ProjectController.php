<?php

namespace App\Http\Controllers\Contractor;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Contracts\View\View;

/**
 * Contractor-facing "My Projects" surface.
 *
 * These routes live under `auth` (NOT the `admin.` group). A contractor may
 * see the projects they are assigned to via the `project_contractors` pivot,
 * including the derived site address taken from the linked customer record.
 *
 * Deliberately NOT exposed to contractors here:
 *  - Quotes (admin-only, customer-scoped).
 *  - Full customer details (company, phone, email, notes, contacts). Only the
 *    customer's `address` string is surfaced, because that is the one piece of
 *    customer data an on-site contractor legitimately needs. The full Customer
 *    model is never passed to the views.
 */
class ProjectController extends Controller
{
    /**
     * List the projects the authenticated contractor is assigned to.
     */
    public function index(): View
    {
        abort_unless(auth()->user()->isContractor(), 403);

        $contractor = auth()->user()->contractor;

        abort_unless($contractor, 403);

        $projects = Project::query()
            ->whereHas('contractors', fn ($query) => $query->whereKey($contractor->id))
            // Eager-load only the address off the customer; never the whole record.
            ->with(['customer:id,address'])
            ->orderBy('name')
            ->paginate(15)
            ->through(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'reference' => $project->reference,
                'state' => $project->state,
                'address' => $project->customer?->address,
            ]);

        return view('contractor.projects.index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Show a single assigned project: name, state and the derived site address.
     */
    public function show(Project $project): View
    {
        // Reuse the existing policy: admin / owning customer / assigned contractor.
        $this->authorize('view', $project);

        // Belt-and-braces: this contractor surface is contractor-only.
        abort_unless(auth()->user()->isContractor(), 403);

        return view('contractor.projects.show', [
            'project' => $project,
            // Pass ONLY the address string, not the customer relation, so no
            // other customer detail can leak into the view.
            'address' => $project->customer?->address,
        ]);
    }
}
