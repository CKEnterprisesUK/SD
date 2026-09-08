<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FolderTemplate;
use App\Models\SharedDocument;
use App\Services\FolderTemplateService;
use App\Services\SharedDocumentService;
use Illuminate\Http\Request;

class FolderTemplateSettingsController extends Controller
{
    /**
     * Show the master folder template editor.
     */
    public function edit(FolderTemplateService $templates)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $folders = $templates->all();

        // Group shared documents by the template folder they belong to, keyed
        // by folder name so the view can render them alongside each folder.
        $sharedDocuments = SharedDocument::orderBy('original_name')
            ->get()
            ->groupBy('folder_template_name');

        return view('admin.settings.folder-template', [
            'folders' => $folders,
            'sharedDocuments' => $sharedDocuments,
        ]);
    }

    /**
     * Replace the master folder template with the submitted ordered set of
     * folders. Persistence is delegated to FolderTemplateService::sync so the
     * set, order and per-role permissions are saved transactionally.
     */
    public function update(Request $request, FolderTemplateService $templates)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $levels = ['read-write', 'read-only', 'no-access'];

        $validated = $request->validate([
            'folders' => ['required', 'array'],
            'folders.*.name' => ['required', 'string', 'max:255'],
            'folders.*.subfolders' => ['nullable', 'array'],
            'folders.*.subfolders.*' => ['required', 'string', 'max:255'],
            'folders.*.permissions.admin' => ['required', 'in:' . implode(',', $levels)],
            'folders.*.permissions.contractor' => ['required', 'in:' . implode(',', $levels)],
            'folders.*.permissions.customer' => ['required', 'in:' . implode(',', $levels)],
        ]);

        $normalized = array_map(function (array $folder): array {
            return [
                'name' => $folder['name'],
                'subfolders' => array_values(array_filter(
                    $folder['subfolders'] ?? [],
                    fn ($subfolder) => is_string($subfolder) && $subfolder !== ''
                )),
                'permissions' => [
                    'admin' => $folder['permissions']['admin'],
                    'contractor' => $folder['permissions']['contractor'],
                    'customer' => $folder['permissions']['customer'],
                ],
            ];
        }, array_values($validated['folders']));

        $templates->sync($normalized);

        // Re-point existing shared documents at the (recreated) template rows
        // so they stay attached to the folder of the same name.
        $this->relinkSharedDocuments();

        return redirect()
            ->route('admin.settings.folder-template.edit')
            ->with('status', 'Folder template updated.');
    }

    /**
     * Upload a shared document and attach it to a template folder. The file is
     * stored once; it is referenced into every new project's copy of that
     * folder at seed time.
     */
    public function storeSharedDocument(
        Request $request,
        FolderTemplate $folderTemplate,
        SharedDocumentService $shared
    ) {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:51200'], // 50 MB
            'locked' => ['nullable', 'boolean'],
        ]);

        $shared->store(
            $validated['file'],
            $folderTemplate,
            $request->boolean('locked', true)
        );

        return redirect()
            ->route('admin.settings.folder-template.edit')
            ->with('status', 'Shared document added.');
    }

    /**
     * Permanently remove a shared document and all of its project references.
     */
    public function destroySharedDocument(
        SharedDocument $sharedDocument,
        SharedDocumentService $shared
    ) {
        abort_unless(auth()->user()->isAdmin(), 403);

        $shared->delete($sharedDocument);

        return redirect()
            ->route('admin.settings.folder-template.edit')
            ->with('status', 'Shared document removed.');
    }

    /**
     * After a template sync (which recreates rows with new ids), re-attach
     * shared documents to the template row that now carries their folder name.
     */
    protected function relinkSharedDocuments(): void
    {
        $byName = FolderTemplate::all()->keyBy('name');

        SharedDocument::all()->each(function (SharedDocument $document) use ($byName): void {
            $template = $byName->get($document->folder_template_name);

            $document->update([
                'folder_template_id' => $template?->id,
            ]);
        });
    }
}
