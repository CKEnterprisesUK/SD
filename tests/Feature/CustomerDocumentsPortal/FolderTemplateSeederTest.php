<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\FolderTemplate;
use Database\Seeders\FolderTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 10: Project libraries seeded faithfully
 *
 * Default case of Property 10: running FolderTemplateSeeder must faithfully
 * produce the master folder template — the five named top-level folders in
 * order, each with the exact default subfolders and the exact per-role
 * permission levels defined in FolderTemplate::DEFAULT_TEMPLATE and enumerated
 * in Requirements 4.6-4.12.
 */
class FolderTemplateSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The expected master template exactly as specified by Requirements 4.6-4.12.
     * Kept independent of DEFAULT_TEMPLATE so the seeder is validated against the
     * requirements themselves, not against its own source of truth.
     *
     * @var list<array{name:string, subfolders:list<string>, permissions:array<string,string>}>
     */
    private const EXPECTED = [
        // 4.6 (folder 1), 4.7 (subfolders), 4.8 (permissions)
        [
            'name' => 'Planning and Design docs',
            'subfolders' => ['Drawings', 'Plans', 'Specifications'],
            'permissions' => ['admin' => 'read-write', 'contractor' => 'read-only', 'customer' => 'read-only'],
        ],
        // 4.6 (folder 2), 4.7, 4.9
        [
            'name' => 'Quotes',
            'subfolders' => ['Issued', 'Accepted'],
            'permissions' => ['admin' => 'read-write', 'contractor' => 'no-access', 'customer' => 'read-only'],
        ],
        // 4.6 (folder 3), 4.7, 4.10
        [
            'name' => 'Build Stage',
            'subfolders' => ['Progress', 'Photos', 'Certificates'],
            'permissions' => ['admin' => 'read-write', 'contractor' => 'read-only', 'customer' => 'read-only'],
        ],
        // 4.6 (folder 4), 4.7, 4.11
        [
            'name' => 'Health and Safety',
            'subfolders' => ['Risk Assessments', 'Method Statements'],
            'permissions' => ['admin' => 'read-write', 'contractor' => 'read-only', 'customer' => 'read-only'],
        ],
        // 4.6 (folder 5), 4.7, 4.12
        [
            'name' => 'SiteDesk Admin Only',
            'subfolders' => ['Internal'],
            'permissions' => ['admin' => 'read-write', 'contractor' => 'no-access', 'customer' => 'no-access'],
        ],
    ];

    /**
     * Assert that the persisted folder_templates rows exactly match the master
     * template required by 4.6-4.12: five folders, correct order, exact
     * subfolders, exact per-role permissions.
     */
    private function assertSeededTemplateIsFaithful(): void
    {
        // 4.6: exactly the five named top-level folders, no more, no less.
        $this->assertSame(
            count(self::EXPECTED),
            FolderTemplate::count(),
            'Expected exactly five top-level folders in the master template.'
        );

        $rows = FolderTemplate::orderBy('sort_order')->get();

        foreach (self::EXPECTED as $index => $expected) {
            $row = $rows[$index];

            // 4.6: named folders appear in the specified order.
            $this->assertSame(
                $expected['name'],
                $row->name,
                "Folder at position {$index} should be [{$expected['name']}]."
            );
            $this->assertSame(
                $index,
                $row->sort_order,
                "Folder [{$expected['name']}] should have sort_order {$index}."
            );

            // 4.7: default subfolders exactly as specified for that folder.
            $this->assertSame(
                $expected['subfolders'],
                $row->subfolders,
                "Folder [{$expected['name']}] should have its default subfolders."
            );

            // 4.8-4.12: exact per-role permission levels for that folder.
            $this->assertSame(
                $expected['permissions'],
                $row->permissions,
                "Folder [{$expected['name']}] should have its default per-role permissions."
            );
        }
    }

    public function test_seeder_produces_faithful_default_master_template(): void
    {
        (new FolderTemplateSeeder())->run();

        $this->assertSeededTemplateIsFaithful();
    }

    /**
     * Property (default case): regardless of what perturbed / partial state the
     * folder_templates table starts in, running the seeder must converge to the
     * exact required master template. We generate >=100 randomized starting
     * states (extra folders, reordered rows, mangled subfolders/permissions,
     * dropped rows) and assert the seeded structure is always faithful.
     */
    public function test_seeder_converges_to_faithful_template_over_random_starting_states(): void
    {
        $roles = ['admin', 'contractor', 'customer'];
        $levels = ['read-write', 'read-only', 'no-access'];

        for ($iteration = 0; $iteration < 120; $iteration++) {
            // Start from a clean table each iteration.
            FolderTemplate::query()->delete();

            // Randomly perturb: sometimes pre-seed with garbage/partial rows that
            // collide with real folder names (exercising updateOrCreate) and/or
            // add unrelated extra rows.
            $realNames = array_column(self::EXPECTED, 'name');

            // 0-5 colliding rows with mangled data that the seeder must overwrite.
            $collisions = array_rand(array_flip($realNames), random_int(1, count($realNames)));
            foreach ((array) $collisions as $name) {
                FolderTemplate::create([
                    'name' => $name,
                    'sort_order' => random_int(50, 99),
                    'subfolders' => [fake()->word(), fake()->word()],
                    'permissions' => [
                        $roles[array_rand($roles)] => $levels[array_rand($levels)],
                    ],
                ]);
            }

            // 0-3 extra unrelated rows that the seeder does NOT touch. To keep the
            // "exactly five" invariant meaningful for the default case, remove any
            // extras before the final assertion — their presence here exercises
            // that the seeder itself does not depend on a clean table.
            $extraNames = [];
            $extraCount = random_int(0, 3);
            for ($e = 0; $e < $extraCount; $e++) {
                // Guaranteed-unique name per iteration without exhausting Faker's
                // finite word list across all 120 loops.
                $extraName = "Extra {$iteration}-{$e}";
                $extraNames[] = $extraName;
                FolderTemplate::create([
                    'name' => $extraName,
                    'sort_order' => random_int(100, 200),
                    'subfolders' => [fake()->word()],
                    'permissions' => [$roles[array_rand($roles)] => $levels[array_rand($levels)]],
                ]);
            }

            // Run the seeder (sometimes twice, to assert idempotency).
            $seeder = new FolderTemplateSeeder();
            $seeder->run();
            if ($iteration % 2 === 0) {
                $seeder->run();
            }

            // Remove unrelated extras so the "exactly five" default invariant holds.
            if ($extraNames !== []) {
                FolderTemplate::whereIn('name', $extraNames)->delete();
            }

            $this->assertSeededTemplateIsFaithful();
        }
    }
}
