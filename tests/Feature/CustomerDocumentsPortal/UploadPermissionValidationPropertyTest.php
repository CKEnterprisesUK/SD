<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\Contractor;
use App\Models\Customer;
use App\Models\FolderPermission;
use App\Models\Project;
use App\Models\ProjectContractor;
use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use App\Models\User;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 15/16: upload permission and validation
 *
 * These properties are exercised end-to-end through the real HTTP routes so the
 * full stack (auth middleware -> EnsureProjectWritable -> DocumentPolicy /
 * FolderPolicy -> controller validation -> DocumentStorageService) participates.
 *
 * Property 15: A non-write user attempting a modifying document/folder operation
 *   is denied and nothing changes. "Non-write" here is an IN-SCOPE customer or
 *   contractor (so scope is not the reason for denial) whose top-level folder
 *   permission is read-only or no-access. For each of upload, delete, copy, and
 *   subfolder-create the request returns 403 and NO change occurs: no
 *   ProjectDocument is created, an existing document is not deleted, no file is
 *   written to the private disk, and no subfolder row is created. Note that
 *   folder management (subfolder create via ProjectFolderController::store) is
 *   admin-only per FolderPolicy::manage, so a non-admin is denied there
 *   regardless of the resolved permission level. Role and permission level are
 *   randomized every iteration.
 *
 * Property 16: An authorized write user (admin) uploading a file that exceeds
 *   the configured maximum size OR is of a disallowed type receives a validation
 *   error on the `file` field (302 redirect back with session errors) and NO
 *   ProjectDocument row is created and NO file is written to the private disk.
 *   The oversized/disallowed choice is randomized every iteration.
 *
 * The two properties together loop over >=100 randomized cases.
 *
 * Validates: Requirements 7.3, 7.4, 9.5
 */
class UploadPermissionValidationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 120;

    /** Non-admin roles used for the non-write actor in Property 15. */
    private const NON_ADMIN_ROLES = ['customer', 'contractor'];

    /** Permission levels that are NOT write (deny upload/delete/copy). */
    private const NON_WRITE_LEVELS = [
        PermissionResolver::READ_ONLY,
        PermissionResolver::NO_ACCESS,
    ];

    /** Controller-configured maximum upload size, in kilobytes. */
    private const MAX_UPLOAD_KB = 20480;

    /**
     * Property 15: non-write in-scope users are denied upload/delete/copy/
     * subfolder-create and nothing changes.
     */
    public function test_non_write_users_denied_modifying_operations(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            Storage::fake('local');

            // A writable (non-Complete) project so the read-only enforcement
            // middleware never fires: the denial must come purely from the
            // permission/authorization layer.
            $project = Project::factory()->create([
                'state' => fake()->randomElement(['Draft', 'Planning', 'Active']),
            ]);

            $topLevel = ProjectFolder::factory()->topLevel()->create([
                'project_id' => $project->id,
            ]);

            // Randomize the acting role and the non-write level granted to it.
            $role = fake()->randomElement(self::NON_ADMIN_ROLES);
            $level = fake()->randomElement(self::NON_WRITE_LEVELS);

            $user = $this->inScopeUserWithLevel($project, $topLevel, $role, $level);

            // A pre-existing document to attempt to delete/copy.
            $document = ProjectDocument::factory()->create([
                'project_folder_id' => $topLevel->id,
            ]);

            $documentsBefore = ProjectDocument::count();
            $foldersBefore = ProjectFolder::count();

            // ---- upload ----
            $uploadResponse = $this->actingAs($user)->post(
                route('admin.projects.documents.store', [$project, $topLevel]),
                ['file' => UploadedFile::fake()->create('note.pdf', 10)]
            );
            $uploadResponse->assertForbidden();

            // ---- delete ----
            $deleteResponse = $this->actingAs($user)->delete(
                route('admin.projects.documents.destroy', [$project, $document])
            );
            $deleteResponse->assertForbidden();

            // ---- copy ----
            $copyResponse = $this->actingAs($user)->post(
                route('admin.projects.documents.copy', [$project, $document]),
                ['destination_folder_id' => $topLevel->id]
            );
            $copyResponse->assertForbidden();

            // ---- subfolder-create (admin-only folder management) ----
            $subfolderResponse = $this->actingAs($user)->post(
                route('admin.projects.folders.store', $project),
                ['name' => 'New Subfolder', 'parent_id' => $topLevel->id]
            );
            $subfolderResponse->assertForbidden();

            // NO change occurred: no new document, the target document still
            // exists, no subfolder was created, and no bytes were written.
            $this->assertSame(
                $documentsBefore,
                ProjectDocument::count(),
                sprintf('Iteration %d (%s/%s): document count must be unchanged.', $i, $role, $level)
            );
            $this->assertDatabaseHas('project_documents', ['id' => $document->id]);
            $this->assertSame(
                $foldersBefore,
                ProjectFolder::count(),
                sprintf('Iteration %d (%s/%s): folder count must be unchanged.', $i, $role, $level)
            );
            $this->assertEmpty(
                Storage::disk('local')->allFiles(),
                sprintf('Iteration %d (%s/%s): nothing must be written to the private disk.', $i, $role, $level)
            );
        }
    }

    /**
     * Property 16: an authorized write user (admin) uploading an oversized or
     * disallowed file gets a validation error and nothing is stored.
     */
    public function test_oversized_or_disallowed_uploads_rejected_without_writing(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            Storage::fake('local');

            $admin = User::factory()->create(['role' => 'admin']);

            $project = Project::factory()->create([
                'state' => fake()->randomElement(['Draft', 'Planning', 'Active']),
            ]);
            $topLevel = ProjectFolder::factory()->topLevel()->create([
                'project_id' => $project->id,
            ]);

            $documentsBefore = ProjectDocument::count();

            $file = $this->invalidUploadedFile();

            $response = $this->actingAs($admin)->post(
                route('admin.projects.documents.store', [$project, $topLevel]),
                ['file' => $file]
            );

            // Validation failure: redirect back with an error on `file`.
            $response->assertStatus(302);
            $response->assertSessionHasErrors('file');

            // NO ProjectDocument row created and NO file written.
            $this->assertSame(
                $documentsBefore,
                ProjectDocument::count(),
                sprintf('Iteration %d: no document row must be created on validation failure.', $i)
            );
            $this->assertEmpty(
                Storage::disk('local')->allFiles(),
                sprintf('Iteration %d: no file must be written on validation failure.', $i)
            );
        }
    }

    /**
     * A randomized invalid upload: either an oversized (but allowed-type) file
     * or an allowed-size file of a disallowed type.
     */
    private function invalidUploadedFile(): UploadedFile
    {
        if (fake()->boolean()) {
            // Oversized: exceeds MAX_UPLOAD_KB but a normally-allowed type.
            return UploadedFile::fake()->create(
                'big.pdf',
                self::MAX_UPLOAD_KB + fake()->numberBetween(1, 10000)
            );
        }

        // Disallowed type at an acceptable size.
        $name = fake()->randomElement(['malware.exe', 'script.sh', 'archive.zip', 'notes.txt']);

        return UploadedFile::fake()->create($name, fake()->numberBetween(1, 100));
    }

    /**
     * Create an in-scope non-admin user for the given role and grant it the
     * given permission level on the top-level folder. The user is always in
     * scope for the project (customer owns it / contractor assigned to it) so
     * the denial in Property 15 is driven by the permission level, never scope.
     */
    private function inScopeUserWithLevel(
        Project $project,
        ProjectFolder $topLevel,
        string $role,
        string $level
    ): User {
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

        FolderPermission::updateOrCreate(
            ['project_folder_id' => $topLevel->id, 'role' => $role],
            ['level' => $level],
        );

        return $user;
    }
}
