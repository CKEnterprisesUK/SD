<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectFolder;
use App\Services\ProjectSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * List projects with their customer and current state.
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:50'],
        ]);

        $projects = Project::query()
            ->with('customer')
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($validated['state'] ?? null, fn ($query, $state) => $query->where('state', $state))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.projects.index', [
            'projects' => $projects,
            'filters' => $validated,
            'states' => Project::STATES,
        ]);
    }

    /**
     * Show the create-project form.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Project::class);

        $selectedCustomerId = $request->query('customer_id');

        if ($selectedCustomerId !== null) {
            $request->validate([
                'customer_id' => ['nullable', 'exists:customers,id'],
            ]);
        }

        return view('admin.projects.create', [
            'customers' => Customer::orderBy('company_name')->orderBy('name')->get(),
            'selectedCustomerId' => $selectedCustomerId,
        ]);
    }

    /**
     * Create a project (state=Draft) and seed its folder library.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'exists:customers,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $project = DB::transaction(function () use ($validated) {
            $project = Project::create([
                'customer_id' => $validated['customer_id'],
                'created_by_user_id' => auth()->id(),
                'name' => $validated['name'],
                'reference' => $validated['reference'] ?? null,
                'description' => $validated['description'] ?? null,
                'state' => 'Draft',
            ]);

            app(ProjectSeeder::class)->seed($project);

            return $project;
        });

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('status', 'Project created successfully.');
    }

    /**
     * Show a single project.
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load([
            'customer',
            'topLevelFolders',
            'contractors',
        ]);

        return view('admin.projects.show', [
            'project' => $project,
        ]);
    }

    /**
     * Browse a single folder within a project (OneDrive-style, one level at a
     * time). Shows the folder's immediate subfolders and documents plus a
     * breadcrumb trail built by walking up the parent chain.
     */
    public function folder(Project $project, ProjectFolder $folder)
    {
        $this->authorize('view', $project);

        // Guard against a folder id from another project.
        abort_unless($folder->project_id === $project->id, 404);

        $folder->load(['children', 'documents']);

        // Build breadcrumb from the top-level ancestor down to this folder.
        $breadcrumbs = collect();
        $node = $folder;

        while ($node !== null) {
            $breadcrumbs->prepend($node);
            $node = $node->parent;
        }

        return view('admin.projects.folder', [
            'project' => $project,
            'folder' => $folder,
            'subfolders' => $folder->children,
            'documents' => $folder->documents,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Show the edit-project form.
     */
    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        return view('admin.projects.edit', [
            'project' => $project,
            'customers' => Customer::orderBy('company_name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Update a project's core details.
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'exists:customers,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $project->update([
            'name' => $validated['name'],
            'customer_id' => $validated['customer_id'],
            'reference' => $validated['reference'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('status', 'Project updated successfully.');
    }

    /**
     * Change a project's workflow state (admin only).
     */
    public function updateState(Request $request, Project $project)
    {
        $this->authorize('changeState', $project);

        $validated = $request->validate([
            'state' => ['required', Rule::in(Project::STATES)],
        ]);

        $project->update([
            'state' => $validated['state'],
        ]);

        return redirect()
            ->back()
            ->with('status', 'Project state updated successfully.');
    }

    /**
     * Assign a contractor to the project.
     */
    public function assignContractor(Request $request, Project $project)
    {
        $this->authorize('manage', $project);

        $validated = $request->validate([
            'contractor_id' => ['required', 'exists:contractors,id'],
        ]);

        $project->contractors()->syncWithoutDetaching([
            $validated['contractor_id'] => ['assigned_by_user_id' => auth()->id()],
        ]);

        return redirect()
            ->back()
            ->with('status', 'Contractor assigned successfully.');
    }

    /**
     * Remove a contractor assignment from the project.
     */
    public function unassignContractor(Project $project, Contractor $contractor)
    {
        $this->authorize('manage', $project);

        $project->contractors()->detach($contractor->id);

        return redirect()
            ->back()
            ->with('status', 'Contractor unassigned successfully.');
    }
}
