<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\ProjectDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 18: no public URL
 *
 * Property 18: No public URL is generated for any document; files reside only on
 * the private disk. This is guaranteed structurally because ProjectDocument
 * intentionally exposes NO URL accessor (no getUrlAttribute()), so Eloquent can
 * never surface a `url` attribute and no public/asset URL can be produced. Every
 * document's storage_path is a private-disk key (a relative path under the
 * `local` disk), never an http/https URL.
 *
 * The test builds >=100 randomized ProjectDocument records via the factory and
 * asserts, for each: (a) toArray() has no `url` key, (b) $document->url is null,
 * (c) no getUrlAttribute method exists on the model, and (d) storage_path is a
 * private-disk key rather than a public URL. A one-time static assertion also
 * confirms the ProjectDocument class source does not define getUrlAttribute.
 *
 * Validates: Requirements 8.2, 8.6
 */
class NoPublicUrlPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 120;

    public function test_project_document_never_exposes_a_public_url(): void
    {
        // One-time static assertions about the model class itself.
        $this->assertFalse(
            method_exists(ProjectDocument::class, 'getUrlAttribute'),
            'ProjectDocument must NOT define a getUrlAttribute() accessor.'
        );

        $reflection = new ReflectionClass(ProjectDocument::class);
        $this->assertFalse(
            $reflection->hasMethod('getUrlAttribute'),
            'ProjectDocument reflection must report no getUrlAttribute() method.'
        );

        // The ProjectDocument class source must not define getUrlAttribute.
        $source = file_get_contents($reflection->getFileName());
        $this->assertIsString($source);
        $this->assertStringNotContainsString(
            'function getUrlAttribute',
            $source,
            'ProjectDocument source must not define a getUrlAttribute() accessor.'
        );

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $document = ProjectDocument::factory()->create();

            // (a) No `url` key surfaces in the serialized attributes.
            $this->assertArrayNotHasKey(
                'url',
                $document->toArray(),
                sprintf('Iteration %d: ProjectDocument::toArray() exposed a "url" key.', $i)
            );

            // (b) Accessing the magic `url` attribute yields null (no accessor).
            $this->assertNull(
                $document->url,
                sprintf('Iteration %d: $document->url should be null (no accessor).', $i)
            );

            // (c) No getUrlAttribute accessor on the instance either.
            $this->assertFalse(
                method_exists($document, 'getUrlAttribute'),
                sprintf('Iteration %d: instance must have no getUrlAttribute() accessor.', $i)
            );

            // (d) storage_path is a private-disk key, not a public http/https URL.
            $storagePath = $document->storage_path;
            $this->assertIsString($storagePath);
            $this->assertStringStartsNotWith(
                'http://',
                $storagePath,
                sprintf('Iteration %d: storage_path must not be an http URL.', $i)
            );
            $this->assertStringStartsNotWith(
                'https://',
                $storagePath,
                sprintf('Iteration %d: storage_path must not be an https URL.', $i)
            );
            $this->assertDoesNotMatchRegularExpression(
                '#^[a-z][a-z0-9+.-]*://#i',
                $storagePath,
                sprintf('Iteration %d: storage_path must be a private-disk key, not a URL.', $i)
            );
        }
    }
}
