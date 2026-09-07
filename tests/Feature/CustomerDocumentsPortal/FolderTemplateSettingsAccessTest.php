<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\Customer;
use App\Models\FolderTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 9: settings admin-only
 *
 * The master folder template configuration screen
 * (admin.settings.folder-template.edit / .update) is admin-only. The
 * FolderTemplateSettingsController guards both actions with
 * abort_unless(auth()->user()->isAdmin(), 403), so any authenticated
 * non-admin user (customer or contractor role) must receive a 403
 * authorization error and never reach FolderTemplateService::sync.
 *
 * An admin, by contrast, is authorized past the gate: the edit action must
 * NOT return 403 (the Blade view is built in task 14, so a missing view could
 * surface as a 500 — we assert the admin is not forbidden rather than
 * asserting 200), and an admin update with a valid folders payload must
 * persist the master template via FolderTemplateService and redirect (302).
 *
 * The non-admin checks are randomized over several dozen users/payloads to
 * give the admin-only gate a property flavor.
 *
 * Validates: Requirements 4.4
 */
class FolderTemplateSettingsAccessTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 40;

    private const NON_ADMIN_ROLES = ['customer', 'contractor'];

    /**
     * Property 9: non-admin users are denied (403) on both the edit (GET) and
     * update (PUT) master-template routes, across many randomized users and
     * payloads. The template is never mutated by a denied request.
     */
    public function test_non_admin_users_are_denied_folder_template_settings(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $role = fake()->randomElement(self::NON_ADMIN_ROLES);
            $user = $this->makeNonAdmin($role);

            // GET edit route => 403
            $editResponse = $this->actingAs($user)
                ->get(route('admin.settings.folder-template.edit'));

            $editResponse->assertStatus(403);

            // PUT update route with a random valid payload => 403
            $payload = ['folders' => $this->randomFolders()];

            $updateResponse = $this->actingAs($user)
                ->put(route('admin.settings.folder-template.update'), $payload);

            $updateResponse->assertStatus(403);

            // A denied request must never have written the master template.
            $this->assertSame(
                0,
                FolderTemplate::count(),
                sprintf(
                    'Iteration %d (%s): denied non-admin request must not persist any template rows.',
                    $i,
                    $role
                )
            );
        }
    }

    /**
     * An admin is authorized past the abort_unless gate on the edit route.
     * The folder-template view is delivered in task 14; if it is missing the
     * route may surface a 500. We therefore assert the admin is NOT forbidden
     * (403) rather than asserting a 200.
     */
    public function test_admin_is_not_forbidden_on_folder_template_edit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Do not let the view-missing case throw; we only care that the admin
        // passes the authorization gate (i.e. is not 403).
        $response = $this->actingAs($admin)
            ->get(route('admin.settings.folder-template.edit'));

        $this->assertNotSame(
            403,
            $response->getStatusCode(),
            'Admin must be authorized past the abort_unless(isAdmin) check on the edit route.'
        );
    }

    /**
     * An admin update with a valid folders payload is authorized and persists
     * the master template via FolderTemplateService, redirecting (302) back to
     * the editor.
     */
    public function test_admin_update_persists_master_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $folders = [
            [
                'name' => 'Planning and Design docs',
                'subfolders' => ['Drawings', 'Plans'],
                'permissions' => [
                    'admin' => 'read-write',
                    'contractor' => 'read-only',
                    'customer' => 'read-only',
                ],
            ],
            [
                'name' => 'SiteDesk Admin Only',
                'subfolders' => ['Internal'],
                'permissions' => [
                    'admin' => 'read-write',
                    'contractor' => 'no-access',
                    'customer' => 'no-access',
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.folder-template.update'), ['folders' => $folders]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.settings.folder-template.edit'));

        // FolderTemplateService::sync should have persisted exactly these rows,
        // in order, with their permissions.
        $this->assertSame(2, FolderTemplate::count());

        $this->assertDatabaseHas('folder_templates', [
            'name' => 'Planning and Design docs',
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('folder_templates', [
            'name' => 'SiteDesk Admin Only',
            'sort_order' => 1,
        ]);

        $stored = FolderTemplate::orderBy('sort_order')->get();

        $this->assertSame(['Drawings', 'Plans'], $stored[0]->subfolders);
        $this->assertSame([
            'admin' => 'read-write',
            'contractor' => 'read-only',
            'customer' => 'read-only',
        ], $stored[0]->permissions);

        $this->assertSame(['Internal'], $stored[1]->subfolders);
        $this->assertSame([
            'admin' => 'read-write',
            'contractor' => 'no-access',
            'customer' => 'no-access',
        ], $stored[1]->permissions);
    }

    /**
     * Build an authenticated non-admin user for the given role. Customers are
     * linked to a Customer record (created via Customer::create) to mirror the
     * real portal wiring.
     */
    private function makeNonAdmin(string $role): User
    {
        if ($role === 'customer') {
            $customer = Customer::create([
                'name' => fake()->company(),
                'status' => 'active',
            ]);

            return User::factory()->create([
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]);
        }

        return User::factory()->create(['role' => 'contractor']);
    }

    /**
     * Produce a random, schema-valid folders payload for the update route.
     *
     * @return array<int, array{name: string, subfolders: array<int, string>, permissions: array<string, string>}>
     */
    private function randomFolders(): array
    {
        $levels = ['read-write', 'read-only', 'no-access'];
        $count = fake()->numberBetween(1, 4);
        $folders = [];

        for ($f = 0; $f < $count; $f++) {
            $subfolderCount = fake()->numberBetween(0, 3);
            $subfolders = [];
            for ($s = 0; $s < $subfolderCount; $s++) {
                $subfolders[] = fake()->unique()->words(2, true);
            }
            fake()->unique(true);

            $folders[] = [
                'name' => fake()->words(2, true),
                'subfolders' => $subfolders,
                'permissions' => [
                    'admin' => fake()->randomElement($levels),
                    'contractor' => fake()->randomElement($levels),
                    'customer' => fake()->randomElement($levels),
                ],
            ];
        }

        return $folders;
    }
}
