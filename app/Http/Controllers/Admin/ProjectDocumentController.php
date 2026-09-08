<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use App\Services\AuditLogger;
use App\Services\DocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Document upload / delete / copy operations for a project library.
 *
 * These actions are the write-side counterpart to the read-only browsing
 * (ProjectLibraryController) and secure streaming (DocumentServeController).
 * All byte operations are delegated to DocumentStorageService, which writes
 * exclusively to the private `local` disk and records an AuditLogger entry.
 *
 * Although this controller lives under the `Admin` namespace and its routes
 * sit in the `admin.` group, the true authorization source is DocumentPolicy:
 *
 *  - upload  -> DocumentPolicy@upload  (canWrite + project not Complete, req 7.3)
 *  - delete  -> DocumentPolicy@delete  (canWrite + project not Complete, req 9.5)
 *  - copy    -> DocumentPolicy@copy    (canWrite on destination + not Complete)
 *
 * Every route is additionally wrapped by the `project.writable`
 * (EnsureProjectWritable) middleware so a Complete project rejects all
 * modifying operations with a read-only 403 as defense in depth (req 2.1/2.4).
 *
 * Upload validation enforces the configured maximum size and an allowlist of
 * document/image mime types (.pdf, .doc/.docx, and common images); oversized or
 * disallowed files are rejected with a validation error and no file is written
 * (req 7.2, 7.4).
 */
class ProjectDocumentController extends Controller
{
    /**
     * Maximum accepted upload size, in kilobytes (20 MB).
     */
    private const MAX_UPLOAD_KB = 20480;

    /**
     * Allowed upload extensions: PDF, Word documents, and common images.
     */
    private const ALLOWED_MIMES = 'pdf,doc,docx,jpg,jpeg,png,gif,webp';

    /**
     * Store an uploaded document into a folder.
     *
     * The folder must belong to the requested project (404 otherwise). Upload
     * is authorized via DocumentPolicy@upload (write access + non-Complete
     * project; 403 otherwise, req 7.3). The file is validated for presence,
     * size, and type before any bytes are written (req 7.2, 7.4).
     */
    public function store(Request $request, Project $project, ProjectFolder $folder): RedirectResponse
    {
        // The folder must belong to the requested project.
        abort_unless($folder->project_id === $project->id, 404);

        // Write access to the folder + project must not be Complete (req 7.3).
        // The `upload` ability lives on DocumentPolicy (mapped to ProjectDocument),
        // so authorize against that policy passing the target folder as argument;
        // authorizing $folder directly would resolve to FolderPolicy, which has
        // no `upload` method and would deny every user (including admins).
        $this->authorize('upload', [ProjectDocument::class, $folder]);

        // Reject oversized or disallowed files before storing anything (req 7.4).
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.self::MAX_UPLOAD_KB,
                'mimes:'.self::ALLOWED_MIMES,
            ],
        ]);

        app(DocumentStorageService::class)->store($request->file('file'), $folder);

        return back()->with('status', 'Document uploaded successfully.');
    }

    /**
     * Delete a document and its private file.
     *
     * The document must belong to the requested project (404 otherwise) and the
     * user must hold write access on the containing folder with the project not
     * Complete (req 9.5).
     */
    public function destroy(Project $project, ProjectDocument $document): RedirectResponse
    {
        // The document's folder must belong to the requested project.
        abort_unless($document->folder->project_id === $project->id, 404);

        // Write access to the containing folder + project not Complete (req 9.5).
        $this->authorize('delete', $document);

        app(DocumentStorageService::class)->delete($document);

        return back()->with('status', 'Document deleted successfully.');
    }

    /**
     * Copy a document into a destination folder within the same project.
     *
     * The source document must belong to the requested project (404 otherwise),
     * the destination folder id must reference a folder in this project, and the
     * user must hold write access on the destination folder with the project not
     * Complete (req 9.3).
     */
    public function copy(Request $request, Project $project, ProjectDocument $document): RedirectResponse
    {
        // The source document's folder must belong to the requested project.
        abort_unless($document->folder->project_id === $project->id, 404);

        $validated = $request->validate([
            'destination_folder_id' => [
                'required',
                Rule::exists('project_folders', 'id')->where('project_id', $project->id),
            ],
        ]);

        $destination = ProjectFolder::where('project_id', $project->id)
            ->findOrFail($validated['destination_folder_id']);

        // Write access to the destination folder + project not Complete (req 9.3).
        // The `copy` ability lives on DocumentPolicy (mapped to ProjectDocument),
        // so authorize against that policy passing the destination folder as the
        // argument; authorizing $destination directly would resolve to
        // FolderPolicy, which has no `copy` method and would deny every user.
        $this->authorize('copy', [ProjectDocument::class, $destination]);

        app(DocumentStorageService::class)->copy($document, $destination);

        return back()->with('status', 'Document copied successfully.');
    }

    /**
     * Move a document into a destination folder within the same project.
     *
     * The source document must belong to the requested project (404 otherwise),
     * the destination folder id must reference a folder in this project, and the
     * user must hold write access on both the source (delete-style) and the
     * destination folder with the project not Complete.
     */
    public function move(Request $request, Project $project, ProjectDocument $document): RedirectResponse
    {
        // The source document's folder must belong to the requested project.
        abort_unless($document->folder->project_id === $project->id, 404);

        // Source-side write access (a locked document may not be moved).
        $this->authorize('delete', $document);

        $validated = $request->validate([
            'destination_folder_id' => [
                'required',
                Rule::exists('project_folders', 'id')->where('project_id', $project->id),
            ],
        ]);

        $destination = ProjectFolder::where('project_id', $project->id)
            ->findOrFail($validated['destination_folder_id']);

        // Write access to the destination folder + project not Complete. The
        // `move` ability lives on DocumentPolicy, so pass the destination folder
        // as the array target so it resolves to DocumentPolicy@move.
        $this->authorize('move', [ProjectDocument::class, $destination]);

        app(DocumentStorageService::class)->move($document, $destination);

        return back()->with('status', 'Document moved successfully.');
    }

    /**
     * Rename a document (its display / original name).
     *
     * The document must belong to the requested project (404 otherwise) and the
     * user must hold write access on the containing folder with the project not
     * Complete. Locked references cannot be renamed (DocumentPolicy@rename).
     */
    public function update(Request $request, Project $project, ProjectDocument $document): RedirectResponse
    {
        abort_unless($document->folder->project_id === $project->id, 404);

        $this->authorize('rename', $document);

        $validated = $request->validate([
            'original_name' => ['required', 'string', 'max:255'],
        ]);

        $previousName = $document->original_name;

        $document->update(['original_name' => $validated['original_name']]);

        AuditLogger::documentRenamed($document, [
            'previous_name' => $previousName,
        ]);

        return back()->with('status', 'Document renamed successfully.');
    }
}
