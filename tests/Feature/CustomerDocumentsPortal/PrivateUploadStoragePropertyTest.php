<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use App\Services\DocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 14: private upload storage
 *
 * Property 14: Uploading a file via DocumentStorageService::store stores the
 * bytes on the private `local` disk and creates a folder-linked ProjectDocument
 * record; every stored Document resides on the private disk and never on the
 * public disk, and its storage key lives under
 * projects/{project_id}/{folder_id}/ (never a public/asset URL).
 *
 * Validates:
 *   Requirements 7.1 — uploaded files are stored and linked to their folder.
 *   Requirements 8.1 — documents are persisted on the private disk only.
 *
 * The test loops over >=100 randomized cases. Each iteration fakes the private
 * and public disks, creates a ProjectFolder, generates a random uploaded file
 * (arbitrary/randomized extension + size, or a fake image), stores it, and
 * asserts all of the private-storage invariants hold.
 */
class PrivateUploadStoragePropertyTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 100;

    private const EXTENSIONS = ['pdf', 'docx', 'txt', 'csv', 'xlsx', 'zip', 'dat'];

    public function test_uploading_stores_on_private_disk_and_links_document(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Fresh fakes each iteration so no bytes leak across cases and the
            // public disk is guaranteed empty at the start of every check.
            Storage::fake('local');
            Storage::fake('public');

            ProjectDocument::query()->delete();
            ProjectFolder::query()->delete();

            $folder = ProjectFolder::factory()->create();

            $file = $this->randomUploadedFile();

            $document = (new DocumentStorageService())->store($file, $folder);

            // A ProjectDocument was created and linked to that folder.
            $this->assertInstanceOf(
                ProjectDocument::class,
                $document,
                "Iteration {$i}: store() must return a ProjectDocument."
            );
            $this->assertDatabaseHas('project_documents', [
                'id' => $document->id,
                'project_folder_id' => $folder->id,
            ]);
            $this->assertSame(
                $folder->id,
                (int) $document->project_folder_id,
                "Iteration {$i}: stored document must be linked to the uploaded folder."
            );

            // The file exists on the private ('local') disk at storage_path.
            Storage::disk('local')->assertExists($document->storage_path);

            // storage_path is under projects/{project_id}/{folder_id}/ ...
            $expectedPrefix = sprintf('projects/%s/%s/', $folder->project_id, $folder->id);
            $this->assertStringStartsWith(
                $expectedPrefix,
                $document->storage_path,
                "Iteration {$i}: storage_path must live under {$expectedPrefix}."
            );

            // ... and is NOT a public/asset URL.
            $this->assertStringNotContainsString(
                '://',
                $document->storage_path,
                "Iteration {$i}: storage_path must be a private disk key, not a URL."
            );
            $this->assertFalse(
                Str::startsWith($document->storage_path, ['http://', 'https://', '/storage', 'storage/']),
                "Iteration {$i}: storage_path must not be a public URL/path."
            );

            // The file does NOT exist on the public disk.
            Storage::disk('public')->assertMissing($document->storage_path);
            $this->assertEmpty(
                Storage::disk('public')->allFiles(),
                "Iteration {$i}: nothing must be written to the public disk."
            );
        }
    }

    /**
     * Generate a randomized UploadedFile: either a fake image or an arbitrary
     * file with a random name, extension, and size.
     */
    private function randomUploadedFile(): UploadedFile
    {
        if (fake()->boolean(30)) {
            return UploadedFile::fake()->image(
                fake()->unique()->lexify('????????').'.'.fake()->randomElement(['jpg', 'png', 'gif']),
                fake()->numberBetween(10, 200),
                fake()->numberBetween(10, 200)
            );
        }

        $name = fake()->unique()->lexify('????????')
            .'.'.fake()->randomElement(self::EXTENSIONS);

        // sizeInKilobytes for create() — randomized, bounded to keep it fast.
        return UploadedFile::fake()->create($name, fake()->numberBetween(1, 512));
    }
}
