<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\Contractor;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectFolder;
use App\Models\User;
use App\Policies\FolderPolicy;
use App\Policies\ProjectPolicy;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 3/12/23: admin-only management
 *
 * Authorization for structural management of the document portal is verified at
 * the POLICY level (ProjectPolicy / FolderPolicy), which is where the design
 * centralizes the rules. Routes/controllers for some of these operations may not
 * exist yet, so exercising the policies directly is both robust and faithful to
 * the design's policy-based authorization.
 *
 * Property 3: Non-admin project create/state-change is denied.
 *   ProjectPolicy::create() and ProjectPolicy::changeState() are true only for
 *   admins; customers and contractors are denied.
 *
 * Property 12: Per-project folder management is admin-only and blocked when the
 *   project is Complete.
 *   FolderPolicy::manage() is true ONLY when the user is admin AND the owning
 *   project is not Complete; it is false for every non-admin and false for an
 *   admin when project.state === 'Complete'.
 *
 * Property 23: Audit log viewing is admin-only.
 *   ProjectPolicy::viewAudit() is true only for admins.
 *
 * Each property loops over >=100 randomized cases with varied users, roles,
 * and project states.
 *
 * Validates: Requirements 1.4, 1.5, 5.1, 5.2, 5.5, 10.4
 */
class AdminOnlyManagementPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 120;

    private const NON_ADMIN_ROLES = ['customer', 'contractor'];

    /**
     * Property 3: create + changeState admin-only.
     */
    public function test_non_admin_project_create_and_state_change_denied(): void
    {
        $policy = new ProjectPolicy();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Admin is always allowed.
            $admin = $this->makeAdmin();
            $adminProject = Project::factory()->create();

            $this->assertTrue(
                $policy->create($admin),
                sprintf('Iteration %d: admin must be allowed to create projects.', $i)
            );
            $this->assertTrue(
                $policy->changeState($admin, $adminProject),
                sprintf('Iteration %d: admin must be allowed to change project state.', $i)
            );

            // Randomly chosen non-admin is always denied.
            $role = fake()->randomElement(self::NON_ADMIN_ROLES);
            [$user, $project] = $this->makeNonAdminActor($role);

            $this->assertFalse(
                $policy->create($user),
                sprintf('Iteration %d (%s): non-admin must NOT create projects.', $i, $role)
            );
            $this->assertFalse(
                $policy->changeState($user, $project),
                sprintf('Iteration %d (%s): non-admin must NOT change project state.', $i, $role)
            );
        }
    }

    /**
     * Property 12: folder manage admin-only + blocked when Complete.
     */
    public function test_folder_management_admin_only_and_blocked_when_complete(): void
    {
        $policy = new FolderPolicy(new PermissionResolver());

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Randomize the project state; roughly half will be Complete.
            $state = fake()->boolean()
                ? 'Complete'
                : fake()->randomElement(['Draft', 'Planning', 'Active']);
            $isComplete = $state === 'Complete';

            $project = Project::factory()->create(['state' => $state]);
            $folder = ProjectFolder::factory()->topLevel()->create([
                'project_id' => $project->id,
            ]);

            // Admin: allowed only when the project is not Complete.
            $admin = $this->makeAdmin();
            $this->assertSame(
                ! $isComplete,
                $policy->manage($admin, $folder),
                sprintf(
                    'Iteration %d: admin manage() must be %s when project state=%s.',
                    $i,
                    $isComplete ? 'false' : 'true',
                    $state
                )
            );

            // Non-admin: always denied regardless of project state.
            $role = fake()->randomElement(self::NON_ADMIN_ROLES);
            [$user] = $this->makeNonAdminActorForProject($role, $project);

            $this->assertFalse(
                $policy->manage($user, $folder),
                sprintf(
                    'Iteration %d (%s): non-admin must NOT manage folders (state=%s).',
                    $i,
                    $role,
                    $state
                )
            );
        }
    }

    /**
     * Property 23: audit log viewing admin-only.
     */
    public function test_audit_log_viewing_admin_only(): void
    {
        $policy = new ProjectPolicy();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $project = Project::factory()->create();

            $admin = $this->makeAdmin();
            $this->assertTrue(
                $policy->viewAudit($admin, $project),
                sprintf('Iteration %d: admin must be allowed to view the audit log.', $i)
            );

            $role = fake()->randomElement(self::NON_ADMIN_ROLES);
            [$user, $scopedProject] = $this->makeNonAdminActor($role);

            // Deny for both an unrelated project and the actor's own scoped one:
            // viewAudit is admin-only, so in-scope must not grant it either.
            $this->assertFalse(
                $policy->viewAudit($user, $project),
                sprintf('Iteration %d (%s): non-admin must NOT view audit log.', $i, $role)
            );
            $this->assertFalse(
                $policy->viewAudit($user, $scopedProject),
                sprintf(
                    'Iteration %d (%s): non-admin must NOT view audit log even when in-scope.',
                    $i,
                    $role
                )
            );
        }
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Build a non-admin actor and an in-scope project the actor can view.
     *
     * @return array{0: User, 1: Project}
     */
    private function makeNonAdminActor(string $role): array
    {
        if ($role === 'customer') {
            $customer = $this->makeCustomer();
            $user = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
            $project = Project::factory()->create(['customer_id' => $customer->id]);

            return [$user, $project];
        }

        // contractor
        $user = User::factory()->create(['role' => 'contractor']);
        $contractor = Contractor::create([
            'user_id' => $user->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
        ]);
        $project = Project::factory()->create();
        $project->contractors()->attach($contractor->id);

        return [$user, $project];
    }

    /**
     * Build a non-admin actor tied to a specific (already-created) project so
     * the actor is in-scope for it.
     *
     * @return array{0: User}
     */
    private function makeNonAdminActorForProject(string $role, Project $project): array
    {
        if ($role === 'customer') {
            $user = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $project->customer_id,
            ]);

            return [$user];
        }

        $user = User::factory()->create(['role' => 'contractor']);
        $contractor = Contractor::create([
            'user_id' => $user->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
        ]);
        $project->contractors()->attach($contractor->id);

        return [$user];
    }

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'name' => fake()->company(),
            'status' => 'active',
        ]);
    }
}
