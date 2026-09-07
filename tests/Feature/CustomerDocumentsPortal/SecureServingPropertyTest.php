<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\Contractor;
use App\Models\Customer;
use App\Models\FolderPermission;
use App\Models\Project;
use App\Models\ProjectContractor;
use App\Models\ProjectFolder;
use App\Models\User;
use App\Services\DocumentStorageService;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 17: secure serving
 *
 * Property 17: The secure serving route GET route('documents.serve', $document)
 * (DocumentServeController@show, guarded by `auth` middleware +
 * DocumentPolicy@download) behaves as follows:
 *   - Unauthenticated request => authentication error (redirect to login / 401).
 *     Guests are denied; no bytes are produced. (req 8.4)
 *   - Authenticated no-access user (in-scope customer/contractor whose folder
 *     role permission is no-access, OR an out-of-scope user) => 403. (req 8.5)
 *   - Authenticated user with at least read-only (admin, or an in-scope role
 *     granted read-only/read-write on the folder) => 200, and the streamed body
 *     equals the exact bytes that were stored on the private disk. (req 8.3, 9.1)
 *
 * The test loops over >=100 randomized cases total across the three branches.
 * Each iteration stores a document via DocumentStorageService::store with
 * known random bytes so the file physically exists on the private `local` disk,
 * then hits the serve route under the chosen actor and asserts the outcome.
 *
 * Validates: Requirements 8.3, 8.4, 8.5, 9.1
 */
class SecureServingPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 120;

    /** Non-admin roles used when granting/denying folder access. */
    private const NON_ADMIN_ROLES = ['customer', 'contractor'];

    /** Levels that grant read access. */
    private const READ_LEVELS = [
        PermissionResolver::READ_ONLY,
        PermissionResolver::READ_WRITE,
    ];

    public function test_serving_denies_guests_and_no_access_and_streams_exact_bytes_for_readers(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Fresh private disk each iteration so bytes never leak across cases.
            Storage::fake('local');

            // Build a project + top-level folder and a document whose known
            // random bytes physically live on the private disk.
            $project = Project::factory()->create([
                'state' => fake()->randomElement(['Draft', 'Planning', 'Active', 'Complete']),
            ]);

            $folder = ProjectFolder::factory()->topLevel()->create([
                'project_id' => $project->id,
            ]);

            [$document, $bytes] = $this->storeDocumentWithKnownBytes($folder);

            $url = route('documents.serve', $document);

            // Randomly pick which of the three branches this iteration exercises.
            $branch = fake()->randomElement(['guest', 'no-access', 'reader']);

            if ($branch === 'guest') {
                $this->assertGuestDenied($url, $i);
            } elseif ($branch === 'no-access') {
                $this->assertNoAccessForbidden($project, $folder, $url, $i);
            } else {
                $this->assertReaderGetsExactBytes($project, $folder, $document, $bytes, $url, $i);
            }
        }
    }

    /**
     * Unauthenticated request: the `auth` middleware denies the guest with a
     * redirect to login (302) or a 401 — never 200 and never document bytes.
     */
    private function assertGuestDenied(string $url, int $i): void
    {
        // Ensure no authenticated user leaks in from a previous iteration:
        // every iteration runs inside a single test method, so a prior
        // actingAs() would otherwise persist into this guest request.
        $this->app['auth']->forgetGuards();

        $response = $this->get($url);

        $status = $response->getStatusCode();

        $this->assertTrue(
            in_array($status, [301, 302, 401, 403], true),
            sprintf(
                'Iteration %d (guest): expected an auth denial (redirect/401), got status %d.',
                $i,
                $status
            )
        );

        // The typical outcome is a redirect to the login route.
        if (in_array($status, [301, 302], true)) {
            $response->assertRedirect(route('login'));
        }

        $this->assertNotSame(
            200,
            $status,
            sprintf('Iteration %d (guest): a guest must never receive a 200.', $i)
        );
    }

    /**
     * Authenticated no-access user: either an in-scope customer/contractor whose
     * folder role permission is no-access (or absent), or an out-of-scope user.
     * Either way the serve route must answer 403.
     */
    private function assertNoAccessForbidden(
        Project $project,
        ProjectFolder $folder,
        string $url,
        int $i
    ): void {
        $outOfScope = fake()->boolean();
        $role = fake()->randomElement(self::NON_ADMIN_ROLES);

        if ($outOfScope) {
            // Out-of-scope user: even a granting permission row cannot help,
            // because the project-scope check fails first.
            $user = $this->makeOutOfScopeUser($role, $project);

            FolderPermission::query()->updateOrCreate(
                ['project_folder_id' => $folder->id, 'role' => $role],
                ['level' => fake()->randomElement(self::READ_LEVELS)]
            );
        } else {
            // In-scope user, but the folder role permission is explicitly
            // no-access (or, randomly, simply absent -> also no-access).
            $user = $this->makeInScopeUser($role, $project);

            if (fake()->boolean()) {
                FolderPermission::query()->updateOrCreate(
                    ['project_folder_id' => $folder->id, 'role' => $role],
                    ['level' => PermissionResolver::NO_ACCESS]
                );
            }
            // else: no permission row at all -> resolver returns no-access.
        }

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(403);
    }

    /**
     * Authenticated reader (admin, or an in-scope role granted read-only /
     * read-write): the route answers 200 and streams back the exact bytes that
     * were written to the private disk.
     */
    private function assertReaderGetsExactBytes(
        Project $project,
        ProjectFolder $folder,
        $document,
        string $bytes,
        string $url,
        int $i
    ): void {
        $useAdmin = fake()->boolean(40);

        if ($useAdmin) {
            $user = User::factory()->create(['role' => 'admin']);
        } else {
            $role = fake()->randomElement(self::NON_ADMIN_ROLES);
            $user = $this->makeInScopeUser($role, $project);

            FolderPermission::query()->updateOrCreate(
                ['project_folder_id' => $folder->id, 'role' => $role],
                ['level' => fake()->randomElement(self::READ_LEVELS)]
            );
        }

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(200);

        // Compare the streamed body against the exact stored bytes.
        $served = $response->streamedContent();

        $this->assertSame(
            $bytes,
            $served,
            sprintf(
                'Iteration %d (reader): streamed body must equal the exact stored bytes '
                .'(expected %d bytes, got %d).',
                $i,
                strlen($bytes),
                strlen($served)
            )
        );
    }

    /**
     * Store a document with known random bytes on the private disk via
     * DocumentStorageService::store, returning [ProjectDocument, rawBytes].
     *
     * @return array{0: \App\Models\ProjectDocument, 1: string}
     */
    private function storeDocumentWithKnownBytes(ProjectFolder $folder): array
    {
        // Known, arbitrary binary payload of a random length.
        $bytes = random_bytes(fake()->numberBetween(16, 4096));

        $extension = fake()->randomElement(['pdf', 'docx', 'txt', 'dat', 'bin']);
        $name = fake()->unique()->lexify('????????').'.'.$extension;

        // A fake UploadedFile carrying our exact bytes as its content.
        $file = UploadedFile::fake()->createWithContent($name, $bytes);

        $document = (new DocumentStorageService())->store($file, $folder);

        // Sanity: the exact bytes really are on the private disk.
        Storage::disk('local')->assertExists($document->storage_path);
        $this->assertSame($bytes, Storage::disk('local')->get($document->storage_path));

        return [$document, $bytes];
    }

    /**
     * Create an in-scope user for the given non-admin role: a customer linked to
     * the project's customer, or a contractor assigned to the project.
     */
    private function makeInScopeUser(string $role, Project $project): User
    {
        if ($role === 'customer') {
            return User::factory()->create([
                'role' => 'customer',
                'customer_id' => $project->customer_id,
            ]);
        }

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

        return $user;
    }

    /**
     * Create an out-of-scope user for the given non-admin role: a customer
     * pointing at a different customer, or a contractor not assigned to the
     * project (optionally assigned to an unrelated project).
     */
    private function makeOutOfScopeUser(string $role, Project $project): User
    {
        if ($role === 'customer') {
            $otherCustomer = Customer::create([
                'name' => fake()->company(),
                'status' => 'active',
            ]);

            return User::factory()->create([
                'role' => 'customer',
                'customer_id' => $otherCustomer->id,
            ]);
        }

        $user = User::factory()->create(['role' => 'contractor']);

        $contractor = Contractor::create([
            'user_id' => $user->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
        ]);

        // Not assigned to $project; optionally assigned to a different project.
        if (fake()->boolean()) {
            $otherProject = Project::factory()->create();
            ProjectContractor::factory()->create([
                'project_id' => $otherProject->id,
                'contractor_id' => $contractor->id,
            ]);
        }

        return $user;
    }
}
