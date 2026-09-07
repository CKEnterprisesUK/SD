<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Services\FolderTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property test for the master folder template save round-trip.
 *
 * Feature: customer-documents-portal, Property 8: Master template save round-trips
 *
 * For any randomly generated master template — an arbitrary number of ordered
 * top-level folders each carrying a randomly ordered list of subfolders and a
 * per-role permission map (read-write / read-only / no-access for admin,
 * contractor, customer) — persisting it through FolderTemplateService::sync()
 * and reloading it through FolderTemplateService::all() yields an identical
 * set, order, subfolders and permissions.
 *
 * Validates: Requirements 4.2, 4.3
 */
class FolderTemplateSaveRoundTripTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 120;

    private const LEVELS = ['read-write', 'read-only', 'no-access'];

    private const ROLES = ['admin', 'contractor', 'customer'];

    public function test_master_template_save_round_trips(): void
    {
        $service = new FolderTemplateService();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $template = $this->randomTemplate();

            $service->sync($template);

            $reloaded = $service->all()
                ->map(fn ($row) => [
                    'name' => $row->name,
                    'subfolders' => $row->subfolders,
                    'permissions' => $row->permissions,
                ])
                ->all();

            $this->assertSame(
                $template,
                $reloaded,
                "Round-trip mismatch on iteration {$i}."
            );

            // Order is preserved: sort_order runs 0..n-1 matching input order.
            $sortOrders = $service->all()->pluck('sort_order')->all();
            $this->assertSame(
                range(0, count($template) - 1),
                $sortOrders,
                "sort_order did not match input order on iteration {$i}."
            );
        }
    }

    /**
     * Build a random master template: 1..6 top-level folders, each with 0..4
     * ordered subfolders and a randomised per-role permission map. Folder names
     * are made unique per template so equality checks are unambiguous.
     *
     * @return array<int, array{name: string, subfolders: array<int, string>, permissions: array<string, string>}>
     */
    private function randomTemplate(): array
    {
        $folderCount = random_int(1, 6);
        $folders = [];

        for ($f = 0; $f < $folderCount; $f++) {
            $folders[] = [
                'name' => "Folder {$f} " . fake()->unique()->words(random_int(1, 3), true),
                'subfolders' => $this->randomSubfolders(),
                'permissions' => $this->randomPermissions(),
            ];
        }

        fake()->unique(true); // reset unique constraint between templates

        return $folders;
    }

    /**
     * @return array<int, string>
     */
    private function randomSubfolders(): array
    {
        $count = random_int(0, 4);
        $subfolders = [];

        for ($s = 0; $s < $count; $s++) {
            $subfolders[] = "Sub {$s} " . fake()->word();
        }

        return $subfolders;
    }

    /**
     * @return array<string, string>
     */
    private function randomPermissions(): array
    {
        $permissions = [];

        foreach (self::ROLES as $role) {
            $permissions[$role] = self::LEVELS[array_rand(self::LEVELS)];
        }

        return $permissions;
    }
}
