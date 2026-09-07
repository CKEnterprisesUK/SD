<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Project;
use App\Services\ProjectSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function create()
    {
        $this->authorize('create', Project::class);

        return view('admin.projects.create', [
            'customers' => Customer::orderBy('company_name')->orderBy('name')->get(),
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
}
