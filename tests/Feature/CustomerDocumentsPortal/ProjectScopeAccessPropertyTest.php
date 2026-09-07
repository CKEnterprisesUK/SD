<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\Contractor;
use App\Models\Customer;
use App\Models\FolderPermission;
use App\Models\Project;
use App\Models\ProjectContractor;
use App\Models\ProjectFolder;
use App\Models\User;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 7: project-scope access
 *
 * Property 7: For any customer user and any Project, access is granted if and
 * only if `project.customer_id === user.customer_id`; and for any contractor
 * and any Project, access is granted if and only if the contractor is assigned
 * to that Project (a row exists in `project_contractors`).
 *
 * PermissionResolver::level() runs a project-scope check before consulting the
 * folder's permission row: an out-of-scope non-admin resolves to no-access
 * regardless of any granting FolderPermission. To make scope the *only* deciding
 * factor, every top-level folder in this test grants the acting role an explicit
 * read-only or read-write permission. Thus, if scope passes the folder is
 * accessible (level !== no-access); if scope fails the folder resolves to
 * no-access even though the permission row would otherwise grant access.
 *
 * The test loops over >=100 randomized cases. For each it randomly chooses a
 * customer or contractor actor, and randomly makes the actor in-scope or
 * out-of-scope for a freshly-built project:
 *   - customer:   matching vs non-matching project.customer_id
 *   - contractor: assigned (project_contractors row) vs unassigned
 * It then asserts canRead()/level() agree exactly with the expected in-scope
 * flag, independently recomputed from the project/user relationship.
 *
 * Validates: Requirements 3.5, 3.6, 3.7
 */
class ProjectScopeAccessPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 120;

    /** Granting levels only — scope, not the folder permission, is the variable. */
    private const GRANTING_LEVELS = [
        PermissionResolver::READ_WRITE,
        PermissionResolver::READ_ONLY,
    ];

    private const ROLES = ['customer', 'contractor'];

    public function test_access_is_scoped_to_owned_or_assigned_projects(): void
    {
        $resolver = new PermissionResolver();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $role = fake()->randomElement(self::ROLES);
            $inScope = fake()->boolean();

            if ($role === 'customer') {
                [$user, $project, $expectedInScope] = $this->buildCustomerCase($inScope);
            } else {
                [$user, $project, $expectedInScope] = $this->buildContractorCase($inScope);
            }

            // A top-level folder that grants the acting role access. Because the
            // permission is a granting level, the ONLY thing that can produce
            // no-access is the project-scope check.
            $level = fake()->randomElement(self::GRANTING_LEVELS);

            $topLevel = ProjectFolder::factory()->topLevel()->create([
                'project_id' => $project->id,
            ]);

            FolderPermission::factory()->create([
                'project_folder_id' => $topLevel->id,
                'role' => $role,
                'level' => $level,
            ]);

            $resolved = $resolver->level($user, $topLevel);
            $canRead = $resolver->canRead($user, $topLevel);

            if ($expectedInScope) {
                // In scope: the granting permission takes effect.
                $this->assertSame(
                    $level,
                    $resolved,
                    sprintf(
                        'Iteration %d (role=%s, in-scope): expected granting level "%s" '
                        .'but resolver returned "%s".',
                        $i,
                        $role,
                        $level,
                        $resolved
                    )
                );
                $this->assertTrue(
                    $canRead,
                    sprintf('Iteration %d (role=%s): in-scope user should have access.', $i, $role)
                );
            } else {
                // Out of scope: no-access despite the granting permission.
                $this->assertSame(
                    PermissionResolver::NO_ACCESS,
                    $resolved,
                    sprintf(
                        'Iteration %d (role=%s, out-of-scope): expected no-access but '
                        .'resolver returned "%s".',
                        $i,
                        $role,
                        $resolved
                    )
                );
                $this->assertFalse(
                    $canRead,
                    sprintf('Iteration %d (role=%s): out-of-scope user must be denied.', $i, $role)
                );
            }
        }
    }

    /**
     * Build a customer actor and project. In-scope means the project's
     * customer_id matches the user's customer_id; out-of-scope means it points
     * at a different customer.
     *
     * @return array{0: User, 1: Project, 2: bool}
     */
    private function buildCustomerCase(bool $inScope): array
    {
        $ownCustomer = $this->makeCustomer();

        $user = User::factory()->create([
            'role' => 'customer',
            'customer_id' => $ownCustomer->id,
        ]);

        if ($inScope) {
            $project = Project::factory()->create(['customer_id' => $ownCustomer->id]);
        } else {
            $otherCustomer = $this->makeCustomer();
            $project = Project::factory()->create(['customer_id' => $otherCustomer->id]);
        }

        return [$user, $project, $inScope];
    }

    /**
     * Build a contractor actor and project. In-scope means a project_contractors
     * row links the contractor to the project; out-of-scope means no such row
     * exists (the contractor may be assigned to an unrelated project instead).
     *
     * @return array{0: User, 1: Project, 2: bool}
     */
    private function buildContractorCase(bool $inScope): array
    {
        $user = User::factory()->create(['role' => 'contractor']);

        $contractor = Contractor::create([
            'user_id' => $user->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
        ]);

        $project = Project::factory()->create();

        if ($inScope) {
            ProjectContractor::factory()->create([
                'project_id' => $project->id,
                'contractor_id' => $contractor->id,
            ]);
        } else {
            // Unassigned: optionally assign this contractor to a *different*
            // project to prove the scope check is per-project, not global.
            if (fake()->boolean()) {
                $otherProject = Project::factory()->create();
                ProjectContractor::factory()->create([
                    'project_id' => $otherProject->id,
                    'contractor_id' => $contractor->id,
                ]);
            }
        }

        return [$user, $project, $inScope];
    }

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'name' => fake()->company(),
            'status' => 'active',
        ]);
    }
}
