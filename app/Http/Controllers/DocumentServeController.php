<?php

namespace App\Http\Controllers;

use App\Models\ProjectDocument;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Secure, authenticated, permission-checked document streaming.
 *
 * This controller is the ONLY route that produces a document's bytes. Documents
 * live exclusively on the private `local` disk (storage/app/private) and are
 * never exposed via a public URL (ProjectDocument defines no URL accessor).
 *
 * Request lifecycle (see design: Secure Document Serving):
 *  - The `auth` middleware guarantees an authenticated user; guests get a
 *    401/login redirect (req 8.4).
 *  - DocumentPolicy@download delegates to PermissionResolver::canRead against
 *    the document's containing folder; no-access yields a 403 (req 8.5).
 *  - A missing file on the private disk yields a 404 (req 8.3).
 *  - A successful read is recorded to the audit log (req 9.1) and the file is
 *    streamed from the private disk (req 8.2).
 */
class DocumentServeController extends Controller
{
    /**
     * Stream a single document from the private disk.
     */
    public function show(ProjectDocument $document): StreamedResponse
    {
        // Authorize download: DocumentPolicy -> PermissionResolver::canRead.
        // A no-access user triggers a 403 authorization error (req 8.5).
        $this->authorize('download', $document);

        // Documents are stored on the private `local` disk (storage/app/private);
        // they are never served from the public disk (req 8.2).
        $disk = Storage::disk('local');

        // Missing storage key -> 404 (req 8.3).
        abort_unless($disk->exists($document->storage_path), 404);

        // Record the download in the audit log (req 9.1).
        AuditLogger::documentDownloaded($document);

        // Stream the file back with its original name and stored mime type.
        return $disk->download($document->storage_path, $document->original_name, [
            'Content-Type' => $document->mime_type ?? 'application/octet-stream',
        ]);
    }
}
