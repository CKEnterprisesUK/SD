<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectFolder;
use App\Services\CustomerActivityLogger;
use App\Services\CustomerPortalInviteService;
use App\Services\PermissionResolver;
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
    public function store(Request $request, CustomerPortalInviteService $inviter)
    {
        $this->authorize('create', Project::class);

        // The customer can either be picked from the existing list
        // (customer_mode=existing) or created inline as part of the project
        // (customer_mode=new). The rules for customer_id vs. the new-customer
        // fields are applied conditionally based on that choice.
        $creatingCustomer = $request->input('customer_mode') === 'new';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'customer_mode' => ['nullable', 'in:existing,new'],
            'customer_id' => [Rule::requiredIf(! $creatingCustomer), 'nullable', 'exists:customers,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],

            // New-customer fields (only required when creating a customer inline).
            'new_customer.name' => [Rule::requiredIf($creatingCustomer), 'nullable', 'string', 'max:255'],
            'new_customer.company_name' => ['nullable', 'string', 'max:255'],
            'new_customer.status' => [Rule::requiredIf($creatingCustomer), 'nullable', 'in:active,inactive,prospect,archived'],
            'new_customer.email' => ['nullable', 'email', 'max:255'],
            'new_customer.phone' => ['nullable', 'string', 'max:255'],
            'new_customer.role' => ['nullable', 'string', 'max:255'],
            'new_customer.address' => ['nullable', 'string', 'max:2000'],
            'new_customer.notes' => ['nullable', 'string', 'max:5000'],
            'new_customer.invite_to_portal' => ['nullable', 'boolean'],
        ]);

        /** @var \App\Models\Customer|null $createdCustomer */
        $createdCustomer = null;

        $project = DB::transaction(function () use ($validated, $creatingCustomer, &$createdCustomer) {
            $customerId = $validated['customer_id'] ?? null;

            if ($creatingCustomer) {
                $createdCustomer = $this->createInlineCustomer($validated['new_customer']);
                $customerId = $createdCustomer->id;
            }

            $project = Project::create([
                'customer_id' => $customerId,
                'created_by_user_id' => auth()->id(),
                'name' => $validated['name'],
                'reference' => $validated['reference'] ?? null,
                'description' => $validated['description'] ?? null,
                'state' => 'Draft',
            ]);

            app(ProjectSeeder::class)->seed($project);

            return $project;
        });

        $status = 'Project created successfully.';

        // Send the portal invite after the transaction commits, mirroring the
        // customer create flow. The main contact was stored from the customer
        // name, so we invite that primary contact when a valid email exists.
        if ($creatingCustomer && ! empty($validated['new_customer']['invite_to_portal'])) {
            $createdCustomer->load('contacts');
            $primary = $createdCustomer->primaryContact ?? $createdCustomer->contacts->firstWhere('email', '!=', null);

            if ($primary && $primary->email) {
                $inviter->invite($createdCustomer, $primary->email, $primary->name ?: $createdCustomer->name, $primary->id);
                $status = 'Project created and Green Street Portal invite sent to the customer.';
            } else {
                $status = 'Project created. No main contact email was available to send a portal invite.';
            }
        }

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('status', $status);
    }

    /**
     * Create a customer inline from the project form. Mirrors the customer
     * creation flow: the customer name is also stored as the primary contact so
     * it never has to be typed twice.
     */
    private function createInlineCustomer(array $data): Customer
    {
        $customer = Customer::create([
            'created_by_user_id' => auth()->id(),
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'status' => $data['status'],
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $customer->contacts()->create([
            'name' => $customer->name,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'] ?? 'Primary contact',
            'is_primary' => true,
            'receives_quotes' => true,
            'receives_invoices' => false,
            'portal_access_enabled' => false,
        ]);

        CustomerActivityLogger::created($customer);

        return $customer;
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
    public function folder(Project $project, ProjectFolder $folder, PermissionResolver $permissions)
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

        // Per-role access map (admin/contractor/customer => level) for the
        // current folder and each subfolder, resolved from the top-level
        // ancestor that carries the permission rows. Drives the colour-coded
        // "who has access" cards on the browse page. Each entry also records
        // whether the folder is top-level, since only top-level folders can
        // have their permissions edited (subfolders inherit).
        $folderAccess = collect([$folder])
            ->merge($folder->children)
            ->mapWithKeys(fn (ProjectFolder $f) => [$f->id => [
                'roles' => $permissions->roleLevels($f),
                'is_top_level' => $f->is_top_level,
            ]])
            ->all();

        // Flat list of every folder in the project, each with an indented
        // "path" label, used to populate the move-destination pickers. Built by
        // walking the tree depth-first from the top-level folders.
        $moveTargets = $this->flattenFolderTree($project);

        return view('admin.projects.folder', [
            'project' => $project,
            'folder' => $folder,
            'subfolders' => $folder->children,
            'documents' => $folder->documents,
            'breadcrumbs' => $breadcrumbs,
            'folderAccess' => $folderAccess,
            'moveTargets' => $moveTargets,
        ]);
    }

    /**
     * Build a flat, depth-ordered list of every folder in the project for the
     * move-destination pickers. Each entry is ['id' => int, 'label' => string]
     * where the label is indented to reflect the folder's depth.
     *
     * @return array<int, array{id:int, label:string}>
     */
    private function flattenFolderTree(Project $project): array
    {
        $roots = $project->topLevelFolders()
            ->with('children')
            ->get();

        $flat = [];

        $walk = function (ProjectFolder $node, int $depth) use (&$walk, &$flat) {
            $flat[] = [
                'id' => $node->id,
                'label' => str_repeat('— ', $depth).$node->name,
            ];

            foreach ($node->children as $child) {
                $walk($child, $depth + 1);
            }
        };

        foreach ($roots as $root) {
            $walk($root, 0);
        }

        return $flat;
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
