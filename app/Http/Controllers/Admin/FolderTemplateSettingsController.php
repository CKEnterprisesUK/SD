<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FolderTemplateService;
use Illuminate\Http\Request;

class FolderTemplateSettingsController extends Controller
{
    /**
     * Show the master folder template editor.
     */
    public function edit(FolderTemplateService $templates)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.settings.folder-template', [
            'folders' => $templates->all(),
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

        return redirect()
            ->route('admin.settings.folder-template.edit')
            ->with('status', 'Folder template updated.');
    }
}
