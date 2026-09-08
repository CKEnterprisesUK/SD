<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\FolderTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 24/25: project discovery and navigation
 *
 * These are HTTP feature tests that exercise project discovery/navigation
 * end-to-end as an authenticated admin (unless testing non-admin denial).
 *
 * Property 24: Create-project from a customer pre-scopes that customer; the
 *   customer record lists that customer's projects (admin-only).
 *   - GET admin.projects.create?customer_id= pre-selects that customer.
 *   - GET admin.customers.show lists that customer's projects and no others.
 *   - The customer record is admin-only (403 for customer/contractor roles).
 *   Validates: Requirements 1.7, 11.1, 11.2.
 *
 * Property 25: The dashboard exposes Projects via an admin-only tile linking
 *   to the projects index page (its own page); non-admins do not see it.
 *   - Admin dashboard shows a Projects tile linking to admin.projects.index.
 *   - Non-admins do not see the Projects tile.
 *   - The projects index page is admin-only and lists projects.
 *   Validates: Requirements 11.3, 11.4, 11.5.
 *
 * Validates: Requirements 1.7, 11.1, 11.2, 11.3, 11.4, 11.5
 */
class ProjectDiscoveryNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the master folder template so any project creation via the store
        // route produces a seeded library (ProjectSeeder runs on store()).
        $this->seed(FolderTemplateSeeder::class);
    }

    // ---------------------------------------------------------------------
    // Property 24: create-from-customer pre-scoping + customer projects list
    // ---------------------------------------------------------------------

    /**
     * Property 24: GET the create-project form with ?customer_id= pre-selects
     * that customer in the dropdown.
     */
    public function test_create_project_from_customer_pre_selects_that_customer(): void
    {
        $this->actingAs($this->makeAdmin());

        // A few randomized iterations for a property flavour.
        for ($i = 0; $i < 8; $i++) {
            $customer = $this->makeCustomer();

            $response = $this->get(route('admin.projects.create', ['customer_id' => $customer->id]));

            $response->assertOk();

            // The page must contain the customer id and render its option as
            // pre-selected. Blade's @selected() renders `value="{id}" selected`.
            $response->assertSee((string) $customer->id, false);
            $response->assertSee('value="'.$customer->id.'" selected', false);
        }
    }

    /**
     * Property 24: The customer record lists that customer's projects and does
     * not leak another customer's projects.
     */
    public function test_customer_record_lists_only_that_customers_projects(): void
    {
        $this->actingAs($this->makeAdmin());

        for ($i = 0; $i < 8; $i++) {
            $customer = $this->makeCustomer();
            $otherCustomer = $this->makeCustomer();

            $ownProjectCount = fake()->numberBetween(1, 4);

            $ownNames = [];
            for ($p = 0; $p < $ownProjectCount; $p++) {
                $name = 'OwnProject '.fake()->unique()->bothify('??-####');
                Project::factory()->create([
                    'customer_id' => $customer->id,
                    'name' => $name,
                ]);
                $ownNames[] = $name;
            }

            $otherName = 'OtherProject '.fake()->unique()->bothify('??-####');
            Project::factory()->create([
                'customer_id' => $otherCustomer->id,
                'name' => $otherName,
            ]);

            $response = $this->get(route('admin.customers.show', $customer));

            $response->assertOk();

            foreach ($ownNames as $name) {
                $response->assertSee($name, false);
            }

            // The other customer's project must not appear on this record.
            $response->assertDontSee($otherName, false);
        }

        fake()->unique(true);
    }

    /**
     * Property 24: The customer record is admin-only. Customer- and
     * contractor-role users receive 403 (CustomerController@show aborts unless
     * the actor isAdmin()).
     */
    public function test_customer_record_is_admin_only(): void
    {
        $customer = $this->makeCustomer();

        $customerUser = User::factory()->create(['role' => 'customer']);
        $contractorUser = User::factory()->create(['role' => 'contractor']);

        $this->actingAs($customerUser)
            ->get(route('admin.customers.show', $customer))
            ->assertForbidden();

        $this->actingAs($contractorUser)
            ->get(route('admin.customers.show', $customer))
            ->assertForbidden();
    }

    // ---------------------------------------------------------------------
    // Property 25: dashboard Projects tile (admin-only) + projects index page
    // ---------------------------------------------------------------------

    /**
     * Property 25: As an admin, the dashboard shows a Projects tile linking to
     * the projects index page.
     */
    public function test_admin_dashboard_shows_projects_tile(): void
    {
        $this->actingAs($this->makeAdmin());

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        // The tile links to the projects index page and carries its label.
        $response->assertSee(route('admin.projects.index'), false);
        $response->assertSee('Open projects', false);
    }

    /**
     * Property 25: Non-admins never see admin tiles. A contractor loading the
     * dashboard must not see the Projects tile.
     */
    public function test_non_admin_dashboard_hides_projects_tile(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);

        $response = $this->actingAs($contractor)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee(route('admin.projects.index'), false);
        $response->assertDontSee('Open projects', false);
    }

    /**
     * Property 25: The projects index page (its own page) is admin-only and
     * lists projects. Admins see each project's name; contractor- and
     * customer-role users receive 403 (ProjectController@index aborts unless
     * the actor isAdmin()).
     */
    public function test_projects_index_page_is_admin_only_and_lists_projects(): void
    {
        $customer = $this->makeCustomer();

        $names = [];
        for ($i = 0; $i < 3; $i++) {
            $name = 'IndexProject '.fake()->unique()->bothify('??-####');
            Project::factory()->create([
                'customer_id' => $customer->id,
                'name' => $name,
            ]);
            $names[] = $name;
        }

        // Admin sees the projects index page with each project listed.
        $adminResponse = $this->actingAs($this->makeAdmin())
            ->get(route('admin.projects.index'));

        $adminResponse->assertOk();
        foreach ($names as $name) {
            $adminResponse->assertSee($name, false);
        }

        // The projects index page is admin-only.
        $contractorUser = User::factory()->create(['role' => 'contractor']);
        $customerUser = User::factory()->create(['role' => 'customer']);

        $this->actingAs($contractorUser)
            ->get(route('admin.projects.index'))
            ->assertForbidden();

        $this->actingAs($customerUser)
            ->get(route('admin.projects.index'))
            ->assertForbidden();

        fake()->unique(true);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'name' => fake()->company(),
            'status' => 'active',
        ]);
    }
}
