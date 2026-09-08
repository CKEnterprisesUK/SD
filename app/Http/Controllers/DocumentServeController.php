<?php

namespace App\Http\Controllers;

use App\Models\ProjectDocument;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
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
    public function show(Request $request, ProjectDocument $document): StreamedResponse
    {
        // Authorize download: DocumentPolicy -> PermissionResolver::canRead.
        // A no-access user triggers a 403 authorization error (req 8.5).
        $this->authorize('download', $document);

        // Documents are stored on the private `local` disk (storage/app/private);
        // they are never served from the public disk (req 8.2).
        $disk = Storage::disk('local');

        // Shared documents are referenced, not duplicated: resolve to the
        // canonical file's key when this row points at a SharedDocument.
        $storagePath = $document->resolvedStoragePath();

        // Missing storage key -> 404 (req 8.3).
        abort_unless($storagePath !== null && $disk->exists($storagePath), 404);

        // Record the read in the audit log (req 9.1).
        AuditLogger::documentDownloaded($document);

        $mime = $document->mime_type ?? 'application/octet-stream';

        // Serve inline when the browser can safely preview the type (images and
        // PDFs), unless the caller explicitly asked to download (?download=1).
        // Everything else falls back to an attachment download.
        $forceDownload = $request->boolean('download');

        if (! $forceDownload && self::isInlineViewable($mime)) {
            return $disk->response($storagePath, $document->original_name, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . addslashes($document->original_name) . '"',
            ]);
        }

        // Stream the file back with its original name and stored mime type.
        return $disk->download($storagePath, $document->original_name, [
            'Content-Type' => $mime,
        ]);
    }

    /**
     * Whether a mime type can be safely previewed inline in the browser.
     *
     * Limited to images and PDFs on purpose: HTML/SVG and other active types
     * are never served inline so an uploaded file cannot execute in the
     * app's origin.
     */
    private static function isInlineViewable(string $mime): bool
    {
        if ($mime === 'application/pdf') {
            return true;
        }

        return str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml';
    }
}
