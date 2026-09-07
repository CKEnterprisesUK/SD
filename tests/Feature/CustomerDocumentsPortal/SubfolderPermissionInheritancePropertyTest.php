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
 * Feature: customer-documents-portal, Property 11: subfolder permission inheritance
 *
 * Property 11: For any folder tree and subfolder, the resolved access level
 * equals the top-level ancestor's level; a newly created subfolder resolves to
 * the same level as its top-level ancestor.
 *
 * This is guaranteed because PermissionResolver::level() resolves every folder
 * through folder.topLevelFolder() and the FolderPermission on that top-level
 * ancestor. The test builds >=100 randomized folder trees (a top-level folder
 * with a FolderPermission for a random role/level, plus nested subfolders at
 * random depths) and asserts, for each subfolder, that the resolved level
 * matches the top-level folder's resolved level. It then appends a brand new
 * subfolder and asserts it too resolves to the same level.
 *
 * Users are kept in-scope (admin acts on any project; customer owns the project;
 * contractor is assigned to the project) so project-scope is never the blocker;
 * only the folder-level inheritance is under test.
 *
 * Validates: Requirements 5.4, 9.4
 */
class SubfolderPermissionInheritancePropertyTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 120;

    private const LEVELS = [
        PermissionResolver::READ_WRITE,
        PermissionResolver::READ_ONLY,
        PermissionResolver::NO_ACCESS,
    ];

    private const ROLES = ['admin', 'customer', 'contractor'];

    public function test_subfolders_resolve_to_top_level_ancestor_level(): void
    {
        $resolver = new PermissionResolver();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            [$user, $role] = $this->randomInScopeUser();
            $project = $this->projectForUser($user, $role);

            // Random permission level applied to the top-level folder for the
            // non-admin role; admins always resolve to read-write regardless.
            $level = fake()->randomElement(self::LEVELS);

            $topLevel = ProjectFolder::factory()->topLevel()->create([
                'project_id' => $project->id,
            ]);

            // A permission row for the acting non-admin role (customer/contractor).
            // Admins ignore permission rows, so only seed for non-admins.
            if ($role !== 'admin') {
                FolderPermission::factory()->create([
                    'project_folder_id' => $topLevel->id,
                    'role' => $role,
                    'level' => $level,
                ]);
            }

            // Build a random nested tree of subfolders (depths 1..4).
            $subfolders = $this->buildRandomSubtree($project, $topLevel);

            $expected = $resolver->level($user, $topLevel);

            foreach ($subfolders as $subfolder) {
                $this->assertSame(
                    $expected,
                    $resolver->level($user, $subfolder),
                    sprintf(
                        'Iteration %d: subfolder #%d (depth chain under top-level #%d) '
                        .'resolved to a different level than its top-level ancestor.',
                        $i,
                        $subfolder->id,
                        $topLevel->id
                    )
                );
            }

            // Add a brand new subfolder beneath a random existing folder and
            // confirm it resolves to the same level as the top-level ancestor.
            $parent = fake()->randomElement(array_merge([$topLevel], $subfolders));

            $newSubfolder = ProjectFolder::factory()->child($parent)->create([
                'project_id' => $project->id,
            ]);

            $this->assertSame(
                $expected,
                $resolver->level($user, $newSubfolder),
                sprintf(
                    'Iteration %d: newly created subfolder #%d did not inherit the '
                    .'top-level ancestor #%d level.',
                    $i,
                    $newSubfolder->id,
                    $topLevel->id
                )
            );
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
        $maxDepth = fake()->numberBetween(1, 4);

        for ($depth = 0; $depth < $maxDepth; $depth++) {
            $nextFrontier = [];

            foreach ($frontier as $parent) {
                $childCount = fake()->numberBetween(1, 3);

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
