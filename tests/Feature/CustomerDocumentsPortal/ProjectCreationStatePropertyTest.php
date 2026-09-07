<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectFolder;
use App\Models\User;
use Database\Seeders\FolderTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 1/2: project creation and state changes
 *
 * These are HTTP feature tests that exercise the admin project routes end-to-end
 * as an authenticated admin user.
 *
 * Property 1: New projects start in Draft with exactly one customer.
 *   POST route('admin.projects.store') with a random valid name/customer_id
 *   creates a Project whose state === 'Draft', whose customer_id equals exactly
 *   the posted customer (one customer, no others), and whose document library
 *   has been seeded (top-level folders exist).
 *   Validates: Requirements 1.1, 1.2.
 *
 * Property 2: State changes persist the selected valid state.
 *   For a created project, PUT route('admin.projects.state.update', $project)
 *   with a random valid state drawn from Project::STATES persists that exact
 *   state to the database.
 *   Validates: Requirement 1.3.
 *
 * Each property loops over >=100 randomized cases.
 *
 * Validates: Requirements 1.1, 1.2, 1.3
 */
class ProjectCreationStatePropertyTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 120;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the master folder template so project creation produces a
        // seeded library (top-level folders) via ProjectSeeder.
        $this->seed(FolderTemplateSeeder::class);
    }

    /**
     * Property 1: New projects start in Draft with exactly one customer, and
     * their document library is seeded.
     */
    public function test_new_projects_start_in_draft_with_exactly_one_customer(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $expectedTopLevelCount = count(\App\Models\FolderTemplate::DEFAULT_TEMPLATE);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $customer = $this->makeCustomer();
            $name = fake()->unique()->words(fake()->numberBetween(1, 4), true);

            $response = $this->post(route('admin.projects.store'), [
                'name' => $name,
                'customer_id' => $customer->id,
            ]);

            // Successful creation redirects (no validation errors).
            $response->assertRedirect();
            $response->assertSessionHasNoErrors();

            $project = Project::query()
                ->where('name', $name)
                ->latest('id')
                ->first();

            $this->assertNotNull(
                $project,
                sprintf('Iteration %d: created project must be persisted.', $i)
            );

            // Property 1: state is Draft.
            $this->assertSame(
                'Draft',
                $project->state,
                sprintf('Iteration %d: new project must start in Draft, got "%s".', $i, $project->state)
            );

            // Property 1: exactly one customer, and it is the posted one.
            $this->assertSame(
                $customer->id,
                $project->customer_id,
                sprintf('Iteration %d: project must be associated with the posted customer.', $i)
            );
            $this->assertSame(
                1,
                Customer::query()->whereKey($project->customer_id)->count(),
                sprintf('Iteration %d: project must be associated with exactly one existing customer.', $i)
            );

            // Property 1: the document library was seeded (top-level folders exist).
            $topLevelCount = ProjectFolder::query()
                ->where('project_id', $project->id)
                ->where('is_top_level', true)
                ->count();

            $this->assertSame(
                $expectedTopLevelCount,
                $topLevelCount,
                sprintf(
                    'Iteration %d: project library must be seeded with %d top-level folders, got %d.',
                    $i,
                    $expectedTopLevelCount,
                    $topLevelCount
                )
            );
        }

        fake()->unique(true);
    }

    /**
     * Property 2: State changes persist the selected valid state.
     */
    public function test_state_changes_persist_the_selected_valid_state(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $customer = $this->makeCustomer();

            // Create a project via the store route so we exercise the real flow.
            $name = fake()->unique()->words(fake()->numberBetween(1, 4), true);
            $this->post(route('admin.projects.store'), [
                'name' => $name,
                'customer_id' => $customer->id,
            ])->assertSessionHasNoErrors();

            $project = Project::query()->where('name', $name)->latest('id')->firstOrFail();

            // Pick a random valid state from the full set of allowed states.
            $targetState = fake()->randomElement(Project::STATES);

            $response = $this->put(route('admin.projects.state.update', $project), [
                'state' => $targetState,
            ]);

            $response->assertSessionHasNoErrors();

            $project->refresh();

            $this->assertSame(
                $targetState,
                $project->state,
                sprintf(
                    'Iteration %d: state change must persist "%s", got "%s".',
                    $i,
                    $targetState,
                    $project->state
                )
            );

            // The persisted value must be one of the allowed states.
            $this->assertContains(
                $project->state,
                Project::STATES,
                sprintf('Iteration %d: persisted state must be a valid Project state.', $i)
            );
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
