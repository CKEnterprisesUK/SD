<?php

namespace Database\Seeders;

use App\Models\FolderTemplate;
use Illuminate\Database\Seeder;

class FolderTemplateSeeder extends Seeder
{
    /**
     * Seed the master folder template: the five ordered top-level folders with
     * their default subfolders and per-role permissions from
     * FolderTemplate::DEFAULT_TEMPLATE. Idempotent — keyed on folder name so
     * re-running refreshes order/subfolders/permissions without duplicating rows.
     */
    public function run(): void
    {
        foreach (FolderTemplate::DEFAULT_TEMPLATE as $sortOrder => $folder) {
            FolderTemplate::updateOrCreate(
                ['name' => $folder['name']],
                [
                    'sort_order' => $sortOrder,
                    'subfolders' => $folder['subfolders'],
                    'permissions' => $folder['permissions'],
                ]
            );
        }
    }
}
