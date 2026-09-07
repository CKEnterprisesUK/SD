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
 * Feature: customer-documents-portal, Property 13: browsable folder set
 *
 * Property 13: The browsable set returned by
 * PermissionResolver::visibleTopLevelFolders(user, project) equals exactly the
 * project's top-level folders whose resolved level !== no-access. A top-level
 * folder that resolves to no-access is excluded from the browsable set, and its
 * whole subtree resolves to no-access too (the entire subtree is hidden from
 * browsing).
 *
 * The test builds >=100 randomized projects. Each project gets a random set of
 * top-level folders, each assigned a random permission level
 * (read-write / read-only / no-access) for the acting in-scope user's role,
 * plus a random nested tree of subfolders beneath each. It then asserts:
 *   1. visibleTopLevelFolders() returns exactly the top-level folders whose
 *      resolved level !== no-access (order-independent set equality by id).
 *   2. For every top-level folder that resolves to no-access, each subfolder in
 *      its subtree also resolves to no-access (whole subtree excluded).
 *   3. For every visible top-level folder, its subtree resolves to a non
 *      no-access level (subtree is browsable, mirroring the ancestor).
 *
 * Acting users are kept in-scope (admin acts on any project; customer owns the
 * project; contractor is assigned to the project) so project-scope is never the
 * blocker; only the folder-level browsable-set behaviour is under test. Note
 * that admins always resolve to read-write, so for admins no top-level folder is
 * ever no-access; the random no-access levels only bite for customer/contractor
 * roles, which the randomized role selection exercises.
 *
 * Validates: Requirements 6.1, 6.3, 6.4
 */
class BrowsableFolderSetPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 120;

    private const LEVELS = [
        PermissionResolver::READ_WRITE,
        PermissionResolver::READ_ONLY,
        PermissionResolver::NO_ACCESS,
    ];

    private const ROLES = ['admin', 'customer', 'contractor'];

    public function test_browsable_set_equals_non_no_access_top_level_folders(): void
    {
        $resolver = new PermissionResolver();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            [$user, $role] = $this->randomInScopeUser();
            $project = $this->projectForUser($user, $role);

            $topLevelCount = fake()->numberBetween(1, 5);

            /** @var array<int, ProjectFolder> $topLevels indexed by folder id */
            $topLevels = [];
            /** @var array<int, list<ProjectFolder>> $subtrees keyed by top-level id */
            $subtrees = [];

            for ($t = 0; $t < $topLevelCount; $t++) {
                $topLevel = ProjectFolder::factory()->topLevel()->create([
                    'project_id' => $project->id,
                    'sort_order' => $t,
                ]);

                // Assign a random permission level for the acting non-admin role.
                // Admins ignore permission rows (always read-write), so only seed
                // rows for customer/contractor. We still draw a random level to
                // keep the distribution varied across roles.
                $level = fake()->randomElement(self::LEVELS);

                if ($role !== 'admin') {
                    // A missing permission row also resolves to no-access; randomly
                    // omit the row when the drawn level is no-access to exercise
                    // both the explicit-no-access and missing-row paths.
                    if ($level !== PermissionResolver::NO_ACCESS || fake()->boolean()) {
                        FolderPermission::factory()->create([
                            'project_folder_id' => $topLevel->id,
                            'role' => $role,
                            'level' => $level,
                        ]);
                    }
                }

                $topLevels[$topLevel->id] = $topLevel;
                $subtrees[$topLevel->id] = $this->buildRandomSubtree($project, $topLevel);
            }

            // Expected browsable set: top-level folders whose resolved level is
            // not no-access, computed independently from level().
            $expectedVisibleIds = [];
            foreach ($topLevels as $id => $topLevel) {
                if ($resolver->level($user, $topLevel) !== PermissionResolver::NO_ACCESS) {
                    $expectedVisibleIds[] = $id;
                }
            }

            $actualVisibleIds = $resolver
                ->visibleTopLevelFolders($user, $project)
                ->pluck('id')
                ->all();

            sort($expectedVisibleIds);
            sort($actualVisibleIds);

            $this->assertSame(
                $expectedVisibleIds,
                $actualVisibleIds,
                sprintf(
                    'Iteration %d (role=%s): visibleTopLevelFolders did not equal the '
                    .'set of top-level folders with resolved level !== no-access.',
                    $i,
                    $role
                )
            );

            // Subtree assertions: a no-access top-level folder hides its whole
            // subtree; a visible top-level folder keeps its subtree browsable.
            foreach ($topLevels as $id => $topLevel) {
                $topLevelLevel = $resolver->level($user, $topLevel);
                $isHidden = $topLevelLevel === PermissionResolver::NO_ACCESS;

                foreach ($subtrees[$id] as $subfolder) {
                    $subLevel = $resolver->level($user, $subfolder);

                    if ($isHidden) {
                        $this->assertSame(
                            PermissionResolver::NO_ACCESS,
                            $subLevel,
                            sprintf(
                                'Iteration %d: subfolder #%d under no-access top-level '
                                .'#%d should also resolve to no-access (subtree hidden).',
                                $i,
                                $subfolder->id,
                                $topLevel->id
                            )
                        );
                    } else {
                        $this->assertNotSame(
                            PermissionResolver::NO_ACCESS,
                            $subLevel,
                            sprintf(
                                'Iteration %d: subfolder #%d under visible top-level '
                                .'#%d should be browsable (not no-access).',
                                $i,
                                $subfolder->id,
                                $topLevel->id
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * Create a random in-scope acting user and return [user, role].
     *
     * @return array{0: User, 1: string}
     */
    private function randomInScopeUser(): array
    {
        $role = fake()->randomElement(self::ROLES);

        if ($role === 'customer') {
            $customer = $this->makeCustomer();
            $user = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
        } elseif ($role === 'contractor') {
            $user = User::factory()->create(['role' => 'contractor']);
            Contractor::create([
                'user_id' => $user->id,
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'status' => 'active',
            ]);
        } else {
            $user = User::factory()->create(['role' => 'admin']);
        }

        return [$user, $role];
    }

    /**
     * Build a project the acting user is in-scope for.
     */
    private function projectForUser(User $user, string $role): Project
    {
        if ($role === 'customer') {
            return Project::factory()->create(['customer_id' => $user->customer_id]);
        }

        $project = Project::factory()->create();

        if ($role === 'contractor') {
            ProjectContractor::factory()->create([
                'project_id' => $project->id,
                'contractor_id' => $user->contractor->id,
            ]);
        }

        return $project;
    }

    /**
     * Build a random nested tree of subfolders beneath the top-level folder.
     *
     * @return list<ProjectFolder>
     */
    private function buildRandomSubtree(Project $project, ProjectFolder $topLevel): array
    {
        $subfolders = [];
        $frontier = [$topLevel];
        $maxDepth = fake()->numberBetween(1, 3);

        for ($depth = 0; $depth < $maxDepth; $depth++) {
            $nextFrontier = [];

            foreach ($frontier as $parent) {
                $childCount = fake()->numberBetween(1, 2);

                for ($c = 0; $c < $childCount; $c++) {
                    $child = ProjectFolder::factory()->child($parent)->create([
                        'project_id' => $project->id,
                    ]);
                    $subfolders[] = $child;
                    $nextFrontier[] = $child;
                }
            }

            $frontier = $nextFrontier;
        }

        return $subfolders;
    }

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'name' => fake()->company(),
            'status' => 'active',
        ]);
    }
}
