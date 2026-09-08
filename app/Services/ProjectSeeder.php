<?php

namespace App\Services;

use App\Models\FolderPermission;
use App\Models\FolderTemplate;
use App\Models\Project;
use App\Models\ProjectFolder;
use App\Models\SharedDocument;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a newly created Project's folder library from the master folder
 * template (FolderTemplate rows). For each ordered template row a top-level
 * ProjectFolder is created carrying per-role FolderPermission rows, followed by
 * child ProjectFolder rows for each configured subfolder. Subfolders never
 * receive their own permission rows — they inherit from their top-level
 * ancestor at resolution time.
 */
class ProjectSeeder
{
    /**
     * Seed the given project's folder tree from the master template.
     *
     * Runs inside a single DB transaction so a partially seeded library is
     * never persisted.
     */
    public function seed(Project $project): void
    {
        $sharedDocuments = app(SharedDocumentService::class);

        DB::transaction(function () use ($project, $sharedDocuments) {
            $templates = FolderTemplate::orderBy('sort_order')->get();

            foreach ($templates as $template) {
                $topLevel = ProjectFolder::create([
                    'project_id' => $project->id,
                    'parent_id' => null,
                    'name' => $template->name,
                    'is_top_level' => true,
                    'sort_order' => $template->sort_order,
                ]);

                foreach (($template->permissions ?? []) as $role => $level) {
                    FolderPermission::create([
                        'project_folder_id' => $topLevel->id,
                        'role' => $role,
                        'level' => $level,
                    ]);
                }

                foreach (array_values($template->subfolders ?? []) as $index => $subfolderName) {
                    ProjectFolder::create([
                        'project_id' => $project->id,
                        'parent_id' => $topLevel->id,
                        'name' => $subfolderName,
                        'is_top_level' => false,
                        'sort_order' => $index,
                    ]);
                }

                // Reference any shared documents attached to this template
                // folder into the project's copy. The bytes are never
                // duplicated — each reference points at the canonical file.
                $shared = SharedDocument::where('folder_template_id', $template->id)
                    ->orWhere('folder_template_name', $template->name)
                    ->get()
                    ->unique('id');

                foreach ($shared as $sharedDocument) {
                    $sharedDocuments->reference($sharedDocument, $topLevel);
                }
            }
        });
    }
}
