<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use App\Services\DocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 19/20: delete and copy
 *
 * Property 19: Deleting a document removes BOTH its ProjectDocument record and
 * its file on the private `local` disk. After delete($document) the database no
 * longer contains the record and Storage::disk('local') no longer contains the
 * stored key.
 *
 * Property 20: Copying a document into a destination folder creates a NEW
 * ProjectDocument record (distinct id, in the destination folder) backed by a
 * NEW private file whose bytes are identical to the source, while the ORIGINAL
 * record and file remain completely unchanged (still present, same bytes,
 * still in their original folder).
 *
 * Both properties are exercised over >=100 randomized iterations. Each
 * iteration stores a document with random byte content through
 * DocumentStorageService::store (which writes to the private disk), then
 * applies either delete or copy and asserts the corresponding invariant. The
 * `local` disk is faked so no real files are touched.
 *
 * Validates: Requirements 9.2, 9.3
 */
class DocumentDeleteCopyPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 120;

    public function test_delete_removes_both_record_and_private_file(): void
    {
        Storage::fake('local');
        $service = new DocumentStorageService();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $folder = $this->randomFolder();
            $document = $service->store($this->randomUpload(), $folder);

            $key = $document->storage_path;
            $documentId = $document->id;

            // Sanity: the store step actually wrote the private file.
            Storage::disk('local')->assertExists($key);
            $this->assertDatabaseHas('project_documents', ['id' => $documentId]);

            $service->delete($document);

            // Property 19: record is gone AND the private file is gone.
            $this->assertDatabaseMissing('project_documents', [
                'id' => $documentId,
            ]);

            Storage::disk('local')->assertMissing($key);
        }
    }

    public function test_copy_creates_new_record_and_identical_file_leaving_original_unchanged(): void
    {
        Storage::fake('local');
        $service = new DocumentStorageService();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $sourceFolder = $this->randomFolder();

            $content = $this->randomContent();
            $original = $service->store($this->randomUpload($content), $sourceFolder);

            $originalId = $original->id;
            $originalKey = $original->storage_path;
            $originalName = $original->original_name;

            // Destination folder in the SAME project (so the copy lands in a
            // valid, distinct folder within the library).
            $destination = ProjectFolder::factory()->child($sourceFolder)->create([
                'project_id' => $sourceFolder->project_id,
            ]);

            $copy = $service->copy($original, $destination);

            // --- Property 20: the copy is a brand-new record + file ----------
            $this->assertNotSame(
                $originalId,
                $copy->id,
                sprintf('Iteration %d: copy must be a new record, not the original.', $i)
            );

            $this->assertNotSame(
                $originalKey,
                $copy->storage_path,
                sprintf('Iteration %d: copy must use a new storage key.', $i)
            );

            $this->assertSame(
                $destination->id,
                $copy->project_folder_id,
                sprintf('Iteration %d: copy must live in the destination folder.', $i)
            );

            $this->assertDatabaseHas('project_documents', [
                'id' => $copy->id,
                'project_folder_id' => $destination->id,
                'storage_path' => $copy->storage_path,
            ]);

            // New private file exists with byte-identical content.
            Storage::disk('local')->assertExists($copy->storage_path);
            $this->assertSame(
                $content,
                Storage::disk('local')->get($copy->storage_path),
                sprintf('Iteration %d: copied file bytes must match the source.', $i)
            );

            // --- Property 20: the ORIGINAL is unchanged ----------------------
            $freshOriginal = ProjectDocument::find($originalId);
            $this->assertNotNull(
                $freshOriginal,
                sprintf('Iteration %d: original record must still exist.', $i)
            );
            $this->assertSame($originalKey, $freshOriginal->storage_path);
            $this->assertSame($sourceFolder->id, $freshOriginal->project_folder_id);
            $this->assertSame($originalName, $freshOriginal->original_name);

            Storage::disk('local')->assertExists($originalKey);
            $this->assertSame(
                $content,
                Storage::disk('local')->get($originalKey),
                sprintf('Iteration %d: original file bytes must be unchanged.', $i)
            );
        }
    }

    /**
     * Create a random top-level folder within a fresh project.
     */
    private function randomFolder(): ProjectFolder
    {
        $project = Project::factory()->create();

        return ProjectFolder::factory()->topLevel()->create([
            'project_id' => $project->id,
        ]);
    }

    /**
     * Build a fake upload carrying the given (or random) byte content.
     */
    private function randomUpload(?string $content = null): UploadedFile
    {
        $content ??= $this->randomContent();

        $extension = fake()->randomElement(['pdf', 'docx', 'png', 'jpg', 'txt']);
        $name = fake()->word().'.'.$extension;

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /**
     * Generate a random, non-empty byte string for document content.
     */
    private function randomContent(): string
    {
        // Mix of readable text and raw bytes to exercise byte-identity checks.
        $length = fake()->numberBetween(1, 2048);

        return random_bytes($length);
    }
}
