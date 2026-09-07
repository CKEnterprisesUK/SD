<?php

namespace App\Services;

use App\Models\FolderTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FolderTemplateService
{
    /**
     * Return the master folder template as an ordered collection of
     * FolderTemplate rows (by sort_order).
     *
     * @return Collection<int, FolderTemplate>
     */
    public function all(): Collection
    {
        return FolderTemplate::orderBy('sort_order')->get();
    }

    /**
     * Replace the entire master folder template with the provided ordered set
     * of folders. Each element is expected to carry a folder name, its ordered
     * list of subfolders and a per-role permission map. The set, order and
     * permissions are persisted transactionally so the template is never left
     * in a partially updated state.
     *
     * @param  array<int, array{name: string, subfolders?: array<int, string>, permissions?: array<string, string>}>  $folders
     * @return Collection<int, FolderTemplate>
     */
    public function sync(array $folders): Collection
    {
        return DB::transaction(function () use ($folders): Collection {
            FolderTemplate::query()->delete();

            foreach (array_values($folders) as $index => $folder) {
                FolderTemplate::create([
                    'name' => $folder['name'],
                    'sort_order' => $index,
                    'subfolders' => array_values($folder['subfolders'] ?? []),
                    'permissions' => $folder['permissions'] ?? [],
                ]);
            }

            return $this->all();
        });
    }
}
