<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\DocumentAuditLog;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use App\Models\User;
use App\Services\DocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 21: document operation auditing
 *
 * Property 21: Upload / download / delete / copy each records an audit entry
 * capturing the actor, action, target document/folder, project, and timestamp.
 *
 * Each document operation is exercised through its real HTTP route as an
 * authenticated admin (who resolves to read-write on every folder, so
 * DocumentPolicy authorizes the operation) against a writable (non-Complete)
 * project (so the `project.writable` / EnsureProjectWritable middleware allows
 * the modifying routes). For every operation a DocumentAuditLog row must be
 * persisted with:
 *   - actor      => user_id of the acting admin
 *   - action     => uploaded / downloaded / deleted / copied
 *   - target     => project_document_id and/or project_folder_id
 *   - project    => project_id
 *   - timestamp  => created_at
 *
 * The `local` disk is faked so no real files are touched.
 *
 * A few randomized iterations are run per operation with at least one explicit
 * assertion each.
 *
 * Validates: Requirements 10.1
 */
class DocumentOperationAuditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Writable (non-Complete) states the middleware/policy permit.
     *
     * @var array<int, string>
     */
    private const WRITABLE_STATES = ['Draft', 'Planning', 'Active'];

    /**
     * Number of randomized iterations per operation.
     */
    private const ITERATIONS = 5;

    protected function setUp(): void
    {
        parent::setUp();

        // Documents live exclusively on the private `local` disk.
        Storage::fake('local');
    }

    /**
     * Uploading a document via the store route records an `uploaded` audit entry
     * linked to the new document + folder, with the acting admin as actor.
     */
    public function test_upload_records_audit_entry(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $admin = $this->makeAdmin();
            $project = $this->makeWritableProject();
            $folder = $this->makeTopLevelFolder($project);
            $upload = $this->fakeUpload();

            $response = $this->actingAs($admin)->post(
                route('admin.projects.documents.store', [$project, $folder]),
                ['file' => $upload],
            );

            $response->assertSessionHasNoErrors();
            $this->assertFalse(
                $response->isForbidden(),
                'Upload route must authorize the admin (403 indicates the upload ability is not resolvable on the folder).'
            );

            $document = ProjectDocument::where('project_folder_id', $folder->id)
                ->where('original_name', $upload->getClientOriginalName())
                ->firstOrFail();

            $entry = DocumentAuditLog::where('action', 'uploaded')
                ->where('project_document_id', $document->id)
                ->first();

            $this->assertNotNull(
                $entry,
                sprintf('Iteration %d: expected an uploaded audit entry for the new document.', $i)
            );
            $this->assertSame($admin->id, $entry->user_id, 'Audit actor must be the acting admin.');
            $this->assertSame('uploaded', $entry->action);
            $this->assertSame($project->id, $entry->project_id, 'Audit entry must target the project.');
            $this->assertSame($document->id, $entry->project_document_id, 'Audit entry must target the document.');
            $this->assertSame($folder->id, $entry->project_folder_id, 'Audit entry must target the folder.');
            $this->assertNotNull($entry->created_at, 'Audit entry must carry a timestamp.');
        }
    }

    /**
     * Downloading a document via the serve route records a `downloaded` audit
     * entry for that document.
     */
    public function test_download_records_audit_entry(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $admin = $this->makeAdmin();
            $project = $this->makeWritableProject();
            $folder = $this->makeTopLevelFolder($project);
            $document = $this->storeDocument($folder);

            $response = $this->actingAs($admin)->get(
                route('documents.serve', $document),
            );

            $response->assertOk();

            $entry = DocumentAuditLog::where('action', 'downloaded')
                ->where('project_document_id', $document->id)
                ->first();

            $this->assertNotNull(
                $entry,
                sprintf('Iteration %d: expected a downloaded audit entry for the document.', $i)
            );
            $this->assertSame($admin->id, $entry->user_id, 'Audit actor must be the acting admin.');
            $this->assertSame('downloaded', $entry->action);
            $this->assertSame($project->id, $entry->project_id, 'Audit entry must target the project.');
            $this->assertSame($document->id, $entry->project_document_id, 'Audit entry must target the document.');
            $this->assertSame($folder->id, $entry->project_folder_id, 'Audit entry must target the folder.');
            $this->assertNotNull($entry->created_at, 'Audit entry must carry a timestamp.');
        }
    }

    /**
     * Deleting a document via the destroy route records a `deleted` audit entry.
     *
     * The audit entry is written before the document row is removed. Because the
     * `document_audit_logs.project_document_id` foreign key is nullOnDelete, the
     * surviving entry's `project_document_id` is set to NULL once the document is
     * gone — the audit trail persists (actor/action/project/timestamp, plus the
     * original name in metadata) even though the document target no longer
     * exists. We therefore scope the assertion to the deleted document's project
     * and confirm the entry carries the original name it targeted, mirroring the
     * folder audit test approach.
     */
    public function test_delete_records_audit_entry(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $admin = $this->makeAdmin();
            $project = $this->makeWritableProject();
            $folder = $this->makeTopLevelFolder($project);
            $document = $this->storeDocument($folder);
            $documentId = $document->id;
            $originalName = $document->original_name;

            $response = $this->actingAs($admin)->delete(
                route('admin.projects.documents.destroy', [$project, $document]),
            );

            $response->assertSessionHasNoErrors();

            $entry = DocumentAuditLog::where('action', 'deleted')
                ->where('project_id', $project->id)
                ->where('metadata->original_name', $originalName)
                ->first();

            $this->assertNotNull(
                $entry,
                sprintf('Iteration %d: expected a deleted audit entry for the document.', $i)
            );
            $this->assertSame($admin->id, $entry->user_id, 'Audit actor must be the acting admin.');
            $this->assertSame('deleted', $entry->action);
            $this->assertSame($project->id, $entry->project_id, 'Audit entry must target the project.');
            $this->assertNotNull($entry->created_at, 'Audit entry must carry a timestamp.');
            // The deleted document's identity is preserved in metadata even after
            // the record (and the project_document_id link) is removed.
            $this->assertSame(
                $originalName,
                $entry->metadata['original_name'] ?? null,
                'Audit entry must record the deleted document original name as its target.'
            );

            // The document itself is gone.
            $this->assertDatabaseMissing('project_documents', ['id' => $documentId]);
        }
    }

    /**
     * Copying a document via the copy route records a `copied` audit entry for
     * the newly created copy in the destination folder.
     */
    public function test_copy_records_audit_entry(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $admin = $this->makeAdmin();
            $project = $this->makeWritableProject();
            $sourceFolder = $this->makeTopLevelFolder($project);
            $destinationFolder = $this->makeTopLevelFolder($project);
            $document = $this->storeDocument($sourceFolder);

            $response = $this->actingAs($admin)->post(
                route('admin.projects.documents.copy', [$project, $document]),
                ['destination_folder_id' => $destinationFolder->id],
            );

            $response->assertSessionHasNoErrors();
            $this->assertFalse(
                $response->isForbidden(),
                'Copy route must authorize the admin (403 indicates the copy ability is not resolvable on the destination folder).'
            );

            $copy = ProjectDocument::where('project_folder_id', $destinationFolder->id)
                ->where('id', '!=', $document->id)
                ->firstOrFail();

            $entry = DocumentAuditLog::where('action', 'copied')
                ->where('project_document_id', $copy->id)
                ->first();

            $this->assertNotNull(
                $entry,
                sprintf('Iteration %d: expected a copied audit entry for the new copy.', $i)
            );
            $this->assertSame($admin->id, $entry->user_id, 'Audit actor must be the acting admin.');
            $this->assertSame('copied', $entry->action);
            $this->assertSame($project->id, $entry->project_id, 'Audit entry must target the project.');
            $this->assertSame($copy->id, $entry->project_document_id, 'Audit entry must target the new copy.');
            $this->assertSame($destinationFolder->id, $entry->project_folder_id, 'Audit entry must target the destination folder.');
            $this->assertNotNull($entry->created_at, 'Audit entry must carry a timestamp.');
        }
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * A project in a writable (non-Complete) state so the project.writable
     * middleware permits modifying document operations.
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
            'name' => ucfirst(fake()->unique()->words(fake()->numberBetween(1, 3), true)),
            'is_top_level' => true,
            'sort_order' => ProjectFolder::where('project_id', $project->id)
                ->whereNull('parent_id')
                ->count(),
        ]);
    }

    /**
     * Store a document into the folder on the (faked) private disk via the
     * real DocumentStorageService, returning the created record.
     */
    private function storeDocument(ProjectFolder $folder): ProjectDocument
    {
        return app(DocumentStorageService::class)->store($this->fakeUpload(), $folder);
    }

    /**
     * Build a fake upload with a random allowed extension and mime type so it
     * passes the store route's `mimes:` validation.
     */
    private function fakeUpload(): UploadedFile
    {
        [$extension, $mime] = fake()->randomElement([
            ['pdf', 'application/pdf'],
            ['png', 'image/png'],
            ['jpg', 'image/jpeg'],
        ]);

        $name = fake()->unique()->word().'.'.$extension;

        return UploadedFile::fake()->create($name, fake()->numberBetween(1, 512), $mime);
    }
}
