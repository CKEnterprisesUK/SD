<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\FolderPermission;
use App\Models\FolderTemplate;
use App\Models\Project;
use App\Models\ProjectFolder;
use App\Services\ProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 10: template-to-project seeding
 *
 * Property 10: For any master template, creating a project and running
 * ProjectSeeder::seed produces a document library whose top-level folders,
 * their order (sort_order), their subfolders, and their per-role permissions
 * all match the master template exactly.
 *
 * Validates: Requirements 4.5 — "WHEN a new Project is created, THE SiteDesk
 * SHALL seed the Project document library from the current Master_Template,
 * including Top_Level_Folders, their order, their Subfolders, and their
 * Permission_Levels."
 *
 * The test loops over >=100 randomized master templates. Each iteration builds
 * a random template (random folder count, random order, random subfolder sets,
 * random per-role permission levels), seeds a fresh Project, and asserts the
 * resulting ProjectFolder tree faithfully reflects the template.
 */
class TemplateToProjectSeedingPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 100;

    private const LEVELS = ['read-write', 'read-only', 'no-access'];

    private const ROLES = ['admin', 'contractor', 'customer'];

    public function test_seeding_reproduces_master_template_faithfully(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Fresh template state each iteration so no cross-contamination.
            FolderPermission::query()->delete();
            ProjectFolder::query()->delete();
            FolderTemplate::query()->delete();

            $expected = $this->buildRandomMasterTemplate();

            $project = Project::factory()->create();

            (new ProjectSeeder())->seed($project);

            $this->assertSeededTreeMatches($project, $expected, $i);
        }
    }

    /**
     * Persist a random master template and return the intended structure keyed
     * by sort_order (the canonical ordering used for both the template and the
     * seeded top-level folders).
     *
     * @return array<int, array{name: string, sort_order: int, subfolders: array<int, string>, permissions: array<string, string>}>
     */
    private function buildRandomMasterTemplate(): array
    {
        $folderCount = fake()->numberBetween(1, 6);

        // Distinct, shuffled sort_order values so ordering is genuinely random
        // and not incidentally equal to insertion order.
        $sortOrders = collect(range(0, $folderCount + 4))
            ->shuffle()
            ->take($folderCount)
            ->values()
            ->all();

        $expected = [];

        foreach ($sortOrders as $sortOrder) {
            $subfolderCount = fake()->numberBetween(0, 4);
            $subfolders = [];
            for ($s = 0; $s < $subfolderCount; $s++) {
                $subfolders[] = fake()->unique()->words(fake()->numberBetween(1, 3), true);
            }

            $permissions = [];
            foreach (self::ROLES as $role) {
                $permissions[$role] = fake()->randomElement(self::LEVELS);
            }

            $name = fake()->unique()->words(fake()->numberBetween(1, 3), true);

            FolderTemplate::create([
                'name' => $name,
                'sort_order' => $sortOrder,
                'subfolders' => $subfolders,
                'permissions' => $permissions,
            ]);

            $expected[$sortOrder] = [
                'name' => $name,
                'sort_order' => $sortOrder,
                'subfolders' => $subfolders,
                'permissions' => $permissions,
            ];
        }

        // Reset uniqueness so subsequent iterations can reuse generated words.
        fake()->unique(true);

        // Canonical ordering by sort_order.
        ksort($expected);

        return array_values($expected);
    }

    /**
     * @param  array<int, array{name: string, sort_order: int, subfolders: array<int, string>, permissions: array<string, string>}>  $expected
     */
    private function assertSeededTreeMatches(Project $project, array $expected, int $iteration): void
    {
        $topLevel = ProjectFolder::query()
            ->where('project_id', $project->id)
            ->where('is_top_level', true)
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(
            count($expected),
            $topLevel,
            "Iteration {$iteration}: top-level folder count must match template folder count."
        );

        foreach ($expected as $index => $templateRow) {
            /** @var ProjectFolder $folder */
            $folder = $topLevel[$index];

            // Top-level folder name + order match the template.
            $this->assertSame(
                $templateRow['name'],
                $folder->name,
                "Iteration {$iteration}: top-level folder name at position {$index} must match template."
            );
            $this->assertSame(
                $templateRow['sort_order'],
                (int) $folder->sort_order,
                "Iteration {$iteration}: top-level folder sort_order at position {$index} must match template."
            );
            $this->assertTrue(
                $folder->is_top_level,
                "Iteration {$iteration}: seeded top-level folder must have is_top_level=true."
            );
            $this->assertNull(
                $folder->parent_id,
                "Iteration {$iteration}: seeded top-level folder must have no parent."
            );

            // Per-role permissions on the top-level folder match the template.
            $permissionRows = FolderPermission::query()
                ->where('project_folder_id', $folder->id)
                ->get();

            $this->assertCount(
                count($templateRow['permissions']),
                $permissionRows,
                "Iteration {$iteration}: top-level folder [{$folder->name}] must have one permission row per templated role."
            );

            $actualPermissions = $permissionRows
                ->mapWithKeys(fn (FolderPermission $p) => [$p->role => $p->level])
                ->all();

            $this->assertSame(
                $templateRow['permissions'],
                $actualPermissions,
                "Iteration {$iteration}: per-role permissions on [{$folder->name}] must match template."
            );

            // Subfolders match the template subfolder names, as children,
            // is_top_level=false, and carry NO permission rows.
            $children = ProjectFolder::query()
                ->where('parent_id', $folder->id)
                ->orderBy('sort_order')
                ->get();

            $this->assertSame(
                array_values($templateRow['subfolders']),
                $children->pluck('name')->all(),
                "Iteration {$iteration}: subfolders of [{$folder->name}] must match template subfolder names and order."
            );

            foreach ($children as $child) {
                $this->assertFalse(
                    $child->is_top_level,
                    "Iteration {$iteration}: subfolder [{$child->name}] must have is_top_level=false."
                );
                $this->assertSame(
                    $project->id,
                    $child->project_id,
                    "Iteration {$iteration}: subfolder [{$child->name}] must belong to the same project."
                );
                $this->assertSame(
                    0,
                    FolderPermission::query()->where('project_folder_id', $child->id)->count(),
                    "Iteration {$iteration}: subfolder [{$child->name}] must not carry permission rows."
                );
            }
        }
    }
}
