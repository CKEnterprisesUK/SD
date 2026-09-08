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
 * Property 25: The dashboard lists all projects paginated for admins and is
 *   not shown to non-admins.
 *   - Admin dashboard renders a paginated Projects table (paginate 15).
 *   - Admin dashboard with zero projects still returns 200.
 *   - Non-admins do not see the Projects table / any project.
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
    // Property 25: dashboard projects table (admin-only, paginated)
    // ---------------------------------------------------------------------

    /**
     * Property 25: As an admin with more than one page of projects, the
     * dashboard shows the Projects table with pagination. The most-recent
     * project appears on page 1; an older project appears on page 2.
     */
    public function test_admin_dashboard_lists_projects_paginated(): void
    {
        $this->actingAs($this->makeAdmin());

        $customer = $this->makeCustomer();

        // Create 20 projects (> paginate(15)) so pagination is exercised.
        $names = [];
        for ($i = 0; $i < 20; $i++) {
            $name = 'DashProject '.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            Project::factory()->create([
                'customer_id' => $customer->id,
                'name' => $name,
            ]);
            $names[] = $name;
        }

        $page1 = $this->get(route('dashboard'));
        $page1->assertOk();
        $page1->assertSee('Projects', false);

        // With paginate(15), page 1 shows at most 15 of the 20 project rows.
        // Determine which names render on page 1 vs page 2 without depending on
        // the exact ordering (timestamp ties make id-order the tiebreaker).
        $page1Content = $page1->getContent();
        $onPage1 = array_values(array_filter(
            $names,
            fn (string $name) => str_contains($page1Content, $name)
        ));

        // Some projects appear on page 1 (the table is populated) but not all
        // 20 — pagination limits the page to 15 rows.
        $this->assertNotEmpty($onPage1, 'Page 1 must list some projects.');
        $this->assertLessThanOrEqual(
            15,
            count($onPage1),
            'Page 1 must show at most 15 project rows (paginate(15)).'
        );
        $this->assertLessThan(
            count($names),
            count($onPage1),
            'Not all 20 projects should fit on page 1 — pagination must split them.'
        );

        // The projects not shown on page 1 must appear on page 2.
        $notOnPage1 = array_values(array_diff($names, $onPage1));
        $this->assertNotEmpty($notOnPage1, 'There must be overflow projects for page 2.');

        $page2 = $this->get(route('dashboard', ['page' => 2]));
        $page2->assertOk();
        foreach ($notOnPage1 as $name) {
            $page2->assertSee($name, false);
        }
    }

    /**
     * Property 25: As an admin with zero projects, the dashboard still renders
     * (200) and shows the empty state for the projects table.
     */
    public function test_admin_dashboard_with_no_projects_still_renders(): void
    {
        $this->actingAs($this->makeAdmin());

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        // Empty LengthAwarePaginator is not empty() == false, so the section
        // renders with its empty-state row.
        $response->assertSee('No projects have been created yet.', false);
    }

    /**
     * Property 25: The projects table is not shown to non-admins. A contractor
     * loading the dashboard must not see any project (their $projects is null,
     * so the section is not rendered).
     */
    public function test_non_admin_dashboard_does_not_show_projects(): void
    {
        $customer = $this->makeCustomer();

        for ($i = 0; $i < 5; $i++) {
            $name = 'HiddenProject '.fake()->unique()->bothify('??-####');
            Project::factory()->create([
                'customer_id' => $customer->id,
                'name' => $name,
            ]);

            $contractor = User::factory()->create(['role' => 'contractor']);

            $response = $this->actingAs($contractor)->get(route('dashboard'));

            $response->assertOk();
            $response->assertDontSee($name, false);
        }

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
