<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\FolderTemplate;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use App\Models\SharedDocument;
use App\Services\DocumentStorageService;
use App\Services\ProjectSeeder;
use App\Services\SharedDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal — shared documents.
 *
 * A shared document (e.g. an insurance certificate) is stored ONCE on the
 * private disk and referenced into a chosen template folder of every newly
 * created project. Locked references cannot be deleted from a project library.
 */
class SharedDocumentReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_document_is_referenced_into_new_projects_without_duplicating_bytes(): void
    {
        Storage::fake('local');

        // A single template folder with a shared, locked document attached.
        $template = FolderTemplate::factory()->create([
            'name' => 'Health and Safety',
            'sort_order' => 0,
            'subfolders' => ['Certificates'],
        ]);

        $shared = app(SharedDocumentService::class)->store(
            UploadedFile::fake()->createWithContent('insurance.pdf', 'CERT-BYTES'),
            $template,
        );

        // The canonical file was written exactly once.
        Storage::disk('local')->assertExists($shared->storage_path);
        $this->assertSame(1, SharedDocument::count());

        // Seed two projects.
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        app(ProjectSeeder::class)->seed($projectA);
        app(ProjectSeeder::class)->seed($projectB);

        // Each project's matching top-level folder has a reference row.
        foreach ([$projectA, $projectB] as $project) {
            $folder = ProjectFolder::where('project_id', $project->id)
                ->where('name', 'Health and Safety')
                ->firstOrFail();

            $reference = $folder->documents()->firstOrFail();

            $this->assertSame($shared->id, $reference->shared_document_id);
            $this->assertTrue($reference->isLocked());
            $this->assertTrue($reference->isSharedReference());
            $this->assertSame($shared->storage_path, $reference->resolvedStoragePath());
        }

        // Bytes stored once: still exactly one file on disk under shared/.
        $this->assertCount(1, Storage::disk('local')->allFiles('shared'));
    }

    public function test_locked_shared_reference_cannot_be_deleted_from_a_project(): void
    {
        Storage::fake('local');

        $template = FolderTemplate::factory()->create(['name' => 'Insurance', 'subfolders' => []]);
        $shared = app(SharedDocumentService::class)->store(
            UploadedFile::fake()->createWithContent('cert.pdf', 'X'),
            $template,
        );

        $project = Project::factory()->create();
        app(ProjectSeeder::class)->seed($project);

        $folder = ProjectFolder::where('project_id', $project->id)
            ->where('name', 'Insurance')
            ->firstOrFail();

        $reference = $folder->documents()->firstOrFail();

        // Deleting a locked reference is refused and leaves the row intact.
        try {
            app(DocumentStorageService::class)->delete($reference);
            $this->fail('Deleting a locked shared reference should throw.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('locked', strtolower($e->getMessage()));
        }

        $this->assertDatabaseHas('project_documents', ['id' => $reference->id]);
        Storage::disk('local')->assertExists($shared->storage_path);
    }

    public function test_deleting_the_shared_document_removes_all_references_and_the_file(): void
    {
        Storage::fake('local');

        $template = FolderTemplate::factory()->create(['name' => 'Insurance', 'subfolders' => []]);
        $shared = app(SharedDocumentService::class)->store(
            UploadedFile::fake()->createWithContent('cert.pdf', 'X'),
            $template,
        );

        $project = Project::factory()->create();
        app(ProjectSeeder::class)->seed($project);

        $this->assertSame(1, ProjectDocument::whereNotNull('shared_document_id')->count());

        app(SharedDocumentService::class)->delete($shared);

        $this->assertSame(0, SharedDocument::count());
        $this->assertSame(0, ProjectDocument::whereNotNull('shared_document_id')->count());
        Storage::disk('local')->assertMissing($shared->storage_path);
    }
}
