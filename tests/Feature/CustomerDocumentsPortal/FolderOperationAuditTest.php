<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\DocumentAuditLog;
use App\Models\Project;
use App\Models\ProjectFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 22: folder operation auditing
 *
 * Property 22: Folder create/rename/reorder/delete each records an audit entry
 * with actor / action / target / timestamp.
 *
 * Each folder operation exercised through ProjectFolderController must persist a
 * DocumentAuditLog row capturing:
 *   - actor      => user_id of the acting (authenticated admin) user
 *   - action     => folder_created / folder_renamed / folder_reordered / folder_deleted
 *   - target     => project_folder_id (and project_id) of the affected folder
 *   - timestamp  => created_at
 *
 * The project is kept in a writable (non-Complete) state so the
 * `project.writable` (EnsureProjectWritable) middleware permits the operation
 * and FolderPolicy::manage (admin-only + not Complete) authorizes it.
 *
 * Some property-style randomization (random folder names, repeated iterations)
 * is used, with at least one explicit assertion per operation.
 *
 * Validates: Requirements 10.2
 */
class FolderOperationAuditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Writable (non-Complete) states the middleware/policy permit.
     *
     * @var array<int, string>
     */
    private const WRITABLE_STATES = ['Draft', 'Planning', 'Active'];

    /**
     * Number of randomized iterations for the create/rename/delete cycle.
     */
    private const ITERATIONS = 5;

    /**
     * Creating a top-level folder records a `folder_created` audit entry with
     * the acting admin as actor, the new folder as target, and a timestamp.
     */
    public function test_folder_create_records_audit_entry(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $admin = $this->makeAdmin();
            $project = $this->makeWritableProject();
            $name = $this->randomFolderName();

            $response = $this->actingAs($admin)->post(
                route('admin.projects.folders.store', $project),
                ['name' => $name],
            );

            $response->assertSessionHasNoErrors();

            $folder = ProjectFolder::where('project_id', $project->id)
                ->where('name', $name)
                ->firstOrFail();

            $entry = DocumentAuditLog::where('action', 'folder_created')
                ->where('project_folder_id', $folder->id)
                ->first();

            $this->assertNotNull(
                $entry,
                sprintf('Iteration %d: expected a folder_created audit entry for the new folder.', $i)
            );
            $this->assertSame($admin->id, $entry->user_id, 'Audit actor must be the acting admin.');
            $this->assertSame('folder_created', $entry->action);
            $this->assertSame($project->id, $entry->project_id, 'Audit entry must target the project.');
            $this->assertSame($folder->id, $entry->project_folder_id, 'Audit entry must target the folder.');
            $this->assertNotNull($entry->created_at, 'Audit entry must carry a timestamp.');
        }
    }

    /**
     * Renaming a folder records a `folder_renamed` audit entry.
     */
    public function test_folder_rename_records_audit_entry(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $admin = $this->makeAdmin();
            $project = $this->makeWritableProject();
            $folder = $this->makeTopLevelFolder($project);
            $newName = $this->randomFolderName();

            $response = $this->actingAs($admin)->put(
                route('admin.projects.folders.update', [$project, $folder]),
                ['name' => $newName],
            );

            $response->assertSessionHasNoErrors();

            $entry = DocumentAuditLog::where('action', 'folder_renamed')
                ->where('project_folder_id', $folder->id)
                ->first();

            $this->assertNotNull(
                $entry,
                sprintf('Iteration %d: expected a folder_renamed audit entry.', $i)
            );
            $this->assertSame($admin->id, $entry->user_id, 'Audit actor must be the acting admin.');
            $this->assertSame('folder_renamed', $entry->action);
            $this->assertSame($project->id, $entry->project_id);
            $this->assertSame($folder->id, $entry->project_folder_id);
            $this->assertNotNull($entry->created_at, 'Audit entry must carry a timestamp.');

            $this->assertDatabaseHas('project_folders', [
                'id' => $folder->id,
                'name' => $newName,
            ]);
        }
    }

    /**
     * Reordering a set of sibling folders records a `folder_reordered` audit
     * entry for each affected folder.
     */
    public function test_folder_reorder_records_audit_entries(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $admin = $this->makeAdmin();
            $project = $this->makeWritableProject();

            // Create three top-level sibling folders.
            $folders = collect(range(0, 2))->map(
                fn () => $this->makeTopLevelFolder($project)
            );

            // Shuffle the ids into a new order.
            $orderedIds = $folders->pluck('id')->shuffle()->values()->all();

            $response = $this->actingAs($admin)->put(
                route('admin.projects.folders.reorder', $project),
                ['folder_ids' => $orderedIds],
            );

            $response->assertSessionHasNoErrors();

            // At least one folder_reordered entry exists for a reordered folder.
            $anyEntry = DocumentAuditLog::where('action', 'folder_reordered')
                ->whereIn('project_folder_id', $orderedIds)
                ->first();

            $this->assertNotNull(
                $anyEntry,
                sprintf('Iteration %d: expected folder_reordered audit entries.', $i)
            );
            $this->assertSame($admin->id, $anyEntry->user_id, 'Audit actor must be the acting admin.');
            $this->assertSame('folder_reordered', $anyEntry->action);
            $this->assertSame($project->id, $anyEntry->project_id);
            $this->assertNotNull($anyEntry->created_at, 'Audit entry must carry a timestamp.');

            // Every reordered folder gets its own audit entry, each targeting
            // the correct folder.
            foreach ($orderedIds as $folderId) {
                $entry = DocumentAuditLog::where('action', 'folder_reordered')
                    ->where('project_folder_id', $folderId)
                    ->first();

                $this->assertNotNull(
                    $entry,
                    sprintf('Iteration %d: folder %d must have a folder_reordered entry.', $i, $folderId)
                );
                $this->assertSame($folderId, $entry->project_folder_id);
            }
        }
    }

    /**
     * Deleting a folder records a `folder_deleted` audit entry.
     *
     * The audit entry is written before the folder row is removed. Because the
     * `document_audit_logs.project_folder_id` foreign key is nullOnDelete, the
     * surviving entry's `project_folder_id` is set to NULL once the folder is
     * gone — the audit trail persists (actor/action/project/timestamp, plus the
     * folder name in metadata) even though the folder target no longer exists.
     * We therefore scope the assertion to the deleted folder's project and
     * confirm the entry carries the folder name it targeted.
     */
    public function test_folder_delete_records_audit_entry(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $admin = $this->makeAdmin();
            $project = $this->makeWritableProject();
            $folder = $this->makeTopLevelFolder($project);
            $folderId = $folder->id;
            $folderName = $folder->name;

            $response = $this->actingAs($admin)->delete(
                route('admin.projects.folders.destroy', [$project, $folder]),
            );

            $response->assertSessionHasNoErrors();

            $entry = DocumentAuditLog::where('action', 'folder_deleted')
                ->where('project_id', $project->id)
                ->first();

            $this->assertNotNull(
                $entry,
                sprintf('Iteration %d: expected a folder_deleted audit entry.', $i)
            );
            $this->assertSame($admin->id, $entry->user_id, 'Audit actor must be the acting admin.');
            $this->assertSame('folder_deleted', $entry->action);
            $this->assertSame($project->id, $entry->project_id, 'Audit entry must target the project.');
            $this->assertNotNull($entry->created_at, 'Audit entry must carry a timestamp.');
            // The deleted folder's identity is preserved in metadata even after
            // the folder row (and the project_folder_id link) is removed.
            $this->assertSame(
                $folderName,
                $entry->metadata['name'] ?? null,
                'Audit entry must record the deleted folder name as its target.'
            );

            // The folder itself is gone.
            $this->assertDatabaseMissing('project_folders', ['id' => $folderId]);
        }
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * A project in a writable (non-Complete) state so the project.writable
     * middleware permits modifying folder operations.
     */
    private function makeWritableProject(): Project
    {
        return Project::factory()->create([
            'state' => fake()->randomElement(self::WRITABLE_STATES),
        ]);
    }

    private function makeTopLevelFolder(Project $project): ProjectFolder
    {
        return ProjectFolder::create([
            'project_id' => $project->id,
            'parent_id' => null,
            'name' => $this->randomFolderName(),
            'is_top_level' => true,
            'sort_order' => ProjectFolder::where('project_id', $project->id)
                ->whereNull('parent_id')
                ->count(),
        ]);
    }

    private function randomFolderName(): string
    {
        return ucfirst(fake()->unique()->words(fake()->numberBetween(1, 3), true));
    }
}
