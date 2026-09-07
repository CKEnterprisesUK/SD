<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Http\Middleware\EnsureProjectWritable;
use App\Models\Contractor;
use App\Models\Customer;
use App\Models\FolderPermission;
use App\Models\Project;
use App\Models\ProjectContractor;
use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use App\Models\User;
use App\Policies\DocumentPolicy;
use App\Policies\FolderPolicy;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 4/5: read-only enforcement
 *
 * Property 4: While a Project is Complete, every modifying document/folder
 * operation is rejected. This is enforced at two independent layers, both
 * exercised here as defense in depth:
 *   1. The EnsureProjectWritable middleware aborts 403 when the route-bound
 *      Project is Complete (so the request never reaches the controller), and
 *      passes the request through ($next invoked) for any non-Complete state.
 *   2. The authorization policies (FolderPolicy::manage / createSubfolder and
 *      DocumentPolicy::upload / delete / copy) all return false for a Complete
 *      project even for an admin / write-capable user.
 *
 * Property 5: While a Project is Complete, reading and downloading are still
 * permitted for users holding at least read-only. FolderPolicy::view and
 * DocumentPolicy::view / download return true for such a user regardless of the
 * Complete state.
 *
 * The test loops over >=100 randomized cases. For the non-Complete branch it
 * randomizes the state among Draft / Planning / Active. The acting user for the
 * write/read policy checks is randomized between an admin (always write-capable)
 * and an in-scope customer / contractor granted read-write on the top-level
 * folder, so the Complete gate — not a lack of permission — is what denies the
 * write. The read assertions use a user granted at least read-only.
 *
 * Validates: Requirements 2.1, 2.2, 2.3, 2.4
 */
class ReadOnlyEnforcementPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 120;

    /** States that must remain writable (non-Complete). */
    private const NON_COMPLETE_STATES = ['Draft', 'Planning', 'Active'];

    /** Roles used for the write-capable acting user. */
    private const WRITE_ROLES = ['admin', 'customer', 'contractor'];

    public function test_middleware_blocks_complete_projects_and_passes_non_complete(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // ---- Complete project: middleware must abort 403 ----
            $complete = Project::factory()->create(['state' => 'Complete']);

            $aborted = false;
            $status = null;
            $nextCalled = false;

            try {
                (new EnsureProjectWritable())->handle(
                    $this->requestBoundToProject($complete),
                    function () use (&$nextCalled) {
                        $nextCalled = true;

                        return response('ok');
                    }
                );
            } catch (HttpException $e) {
                $aborted = true;
                $status = $e->getStatusCode();
            }

            $this->assertTrue(
                $aborted,
                sprintf('Iteration %d: middleware should abort for a Complete project.', $i)
            );
            $this->assertSame(
                403,
                $status,
                sprintf('Iteration %d: middleware abort status should be 403.', $i)
            );
            $this->assertFalse(
                $nextCalled,
                sprintf('Iteration %d: $next must not be called for a Complete project.', $i)
            );

            // ---- Non-Complete project: middleware must pass through ----
            $state = fake()->randomElement(self::NON_COMPLETE_STATES);
            $writable = Project::factory()->create(['state' => $state]);

            $passedNextCalled = false;

            $response = (new EnsureProjectWritable())->handle(
                $this->requestBoundToProject($writable),
                function () use (&$passedNextCalled) {
                    $passedNextCalled = true;

                    return response('ok');
                }
            );

            $this->assertTrue(
                $passedNextCalled,
                sprintf(
                    'Iteration %d (state=%s): $next must be called for a non-Complete project.',
                    $i,
                    $state
                )
            );
            $this->assertSame(
                'ok',
                $response->getContent(),
                sprintf('Iteration %d (state=%s): middleware should return $next result.', $i, $state)
            );
        }
    }

    public function test_policies_block_writes_but_allow_reads_when_complete(): void
    {
        $folderPolicy = new FolderPolicy(new PermissionResolver());
        $documentPolicy = new DocumentPolicy(new PermissionResolver());

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $complete = Project::factory()->create(['state' => 'Complete']);
            $topLevel = ProjectFolder::factory()->topLevel()->create([
                'project_id' => $complete->id,
            ]);
            // A subfolder to prove nested folders are gated identically.
            $subfolder = ProjectFolder::factory()->child($topLevel)->create([
                'project_id' => $complete->id,
            ]);
            $document = ProjectDocument::factory()->create([
                'project_folder_id' => $topLevel->id,
            ]);

            // A write-capable acting user (admin, or an in-scope non-admin
            // granted read-write on the top-level folder). Under a non-Complete
            // project this user could write; the Complete state is what denies.
            $writer = $this->writeCapableUser($complete, $topLevel);

            // Property 4: every modifying folder/document policy denies.
            $this->assertFalse(
                $folderPolicy->manage($writer, $topLevel),
                sprintf('Iteration %d: FolderPolicy::manage must be false when Complete.', $i)
            );
            $this->assertFalse(
                $folderPolicy->createSubfolder($writer, $topLevel),
                sprintf('Iteration %d: FolderPolicy::createSubfolder must be false when Complete.', $i)
            );
            $this->assertFalse(
                $folderPolicy->createSubfolder($writer, $subfolder),
                sprintf('Iteration %d: createSubfolder on a subfolder must be false when Complete.', $i)
            );
            $this->assertFalse(
                $documentPolicy->upload($writer, $topLevel),
                sprintf('Iteration %d: DocumentPolicy::upload must be false when Complete.', $i)
            );
            $this->assertFalse(
                $documentPolicy->delete($writer, $document),
                sprintf('Iteration %d: DocumentPolicy::delete must be false when Complete.', $i)
            );
            $this->assertFalse(
                $documentPolicy->copy($writer, $topLevel),
                sprintf('Iteration %d: DocumentPolicy::copy must be false when Complete.', $i)
            );

            // Property 5: reading/downloading still allowed for a user with at
            // least read-only, regardless of the Complete state.
            $reader = $this->readCapableUser($complete, $topLevel);

            $this->assertTrue(
                $folderPolicy->view($reader, $topLevel),
                sprintf('Iteration %d: FolderPolicy::view must be true for a reader when Complete.', $i)
            );
            $this->assertTrue(
                $folderPolicy->view($reader, $subfolder),
                sprintf('Iteration %d: FolderPolicy::view on a subfolder must be true for a reader.', $i)
            );
            $this->assertTrue(
                $documentPolicy->view($reader, $document),
                sprintf('Iteration %d: DocumentPolicy::view must be true for a reader when Complete.', $i)
            );
            $this->assertTrue(
                $documentPolicy->download($reader, $document),
                sprintf('Iteration %d: DocumentPolicy::download must be true for a reader when Complete.', $i)
            );
        }
    }

    /**
     * Build a real HTTP request whose route binds `project` to the given model,
     * mirroring how EnsureProjectWritable resolves it via $request->route().
     */
    private function requestBoundToProject(Project $project): Request
    {
        $request = Request::create('/admin/projects/'.$project->id.'/folders', 'POST');

        $route = new Route(['POST'], '/admin/projects/{project}/folders', []);
        $route->bind($request);
        $route->setParameter('project', $project);

        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    /**
     * A user who would be able to write to the folder if the project were not
     * Complete: randomly an admin, or an in-scope customer/contractor granted
     * read-write on the top-level folder.
     */
    private function writeCapableUser(Project $project, ProjectFolder $topLevel): User
    {
        return $this->inScopeUserWithLevel(
            $project,
            $topLevel,
            fake()->randomElement(self::WRITE_ROLES),
            PermissionResolver::READ_WRITE
        );
    }

    /**
     * A user who can read the folder: randomly an admin, or an in-scope
     * customer/contractor granted at least read-only on the top-level folder.
     */
    private function readCapableUser(Project $project, ProjectFolder $topLevel): User
    {
        $level = fake()->randomElement([
            PermissionResolver::READ_WRITE,
            PermissionResolver::READ_ONLY,
        ]);

        return $this->inScopeUserWithLevel(
            $project,
            $topLevel,
            fake()->randomElement(self::WRITE_ROLES),
            $level
        );
    }

    /**
     * Create an in-scope user for the given role and, for non-admins, grant the
     * given permission level on the top-level folder. Admins ignore permission
     * rows (always read-write) and are always in scope.
     */
    private function inScopeUserWithLevel(
        Project $project,
        ProjectFolder $topLevel,
        string $role,
        string $level
    ): User {
        if ($role === 'admin') {
            return User::factory()->create(['role' => 'admin']);
        }

        if ($role === 'customer') {
            $user = User::factory()->create([
                'role' => 'customer',
                'customer_id' => $project->customer_id,
            ]);
        } else {
            $user = User::factory()->create(['role' => 'contractor']);
            $contractor = Contractor::create([
                'user_id' => $user->id,
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'status' => 'active',
            ]);
            ProjectContractor::factory()->create([
                'project_id' => $project->id,
                'contractor_id' => $contractor->id,
            ]);
        }

        // Use updateOrCreate: the writer and reader phases may draw the same
        // role for the same top-level folder, which would violate the unique
        // (project_folder_id, role) constraint on a second insert. Each phase
        // creates its own user but shares the folder, so re-granting simply
        // sets the level this phase needs.
        FolderPermission::query()->updateOrCreate(
            [
                'project_folder_id' => $topLevel->id,
                'role' => $role,
            ],
            [
                'level' => $level,
            ]
        );

        return $user;
    }
}
