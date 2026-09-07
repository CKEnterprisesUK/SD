<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Models\Contractor;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\CustomerInvitation;
use App\Models\DocumentAuditLog;
use App\Models\FolderPermission;
use App\Models\FolderTemplate;
use App\Models\Project;
use App\Models\ProjectContractor;
use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checkpoint (task 3): migrate the schema and confirm every new model and its
 * relations resolve without error. RefreshDatabase runs all migrations against
 * the test database, exercising the migration timestamp ordering and inter-table
 * foreign-key dependencies. Each relation is then touched to confirm it loads.
 *
 * Feature: customer-documents-portal
 */
class ModelRelationsCheckpointTest extends TestCase
{
    use RefreshDatabase;

    /** Customer/CustomerContact/Contractor have no factories yet; create inline. */
    private function makeCustomer(): Customer
    {
        return Customer::create([
            'name' => fake()->company(),
            'status' => 'active',
        ]);
    }

    private function makeContractor(): Contractor
    {
        return Contractor::create([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
        ]);
    }

    private function makeCustomerContact(Customer $customer): CustomerContact
    {
        return CustomerContact::create([
            'customer_id' => $customer->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ]);
    }

    public function test_migrations_run_and_all_new_tables_exist(): void
    {
        $tables = [
            'projects',
            'project_folders',
            'folder_permissions',
            'project_documents',
            'project_contractors',
            'document_audit_logs',
            'folder_templates',
            'customer_invitations',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                \Schema::hasTable($table),
                "Expected table [{$table}] to exist after migrations."
            );
        }

        $this->assertTrue(
            \Schema::hasColumn('users', 'customer_id'),
            'Expected users.customer_id column to exist after migrations.'
        );
    }

    public function test_user_customer_relation_resolves(): void
    {
        $customer = $this->makeCustomer();
        $user = User::factory()->create(['customer_id' => $customer->id, 'role' => 'customer']);

        $this->assertTrue($user->relationLoaded('customer') || $user->customer()->exists());
        $this->assertInstanceOf(Customer::class, $user->customer);
        $this->assertTrue($user->isCustomer());
    }

    public function test_project_relations_resolve(): void
    {
        $project = Project::factory()->create();

        // Build a small tree of related records.
        $topFolder = ProjectFolder::factory()->topLevel()->create(['project_id' => $project->id]);
        $childFolder = ProjectFolder::factory()->child($topFolder)->create();
        FolderPermission::factory()->create(['project_folder_id' => $topFolder->id]);
        ProjectDocument::factory()->create(['project_folder_id' => $childFolder->id]);

        $contractor = $this->makeContractor();
        ProjectContractor::factory()->create([
            'project_id' => $project->id,
            'contractor_id' => $contractor->id,
        ]);
        DocumentAuditLog::factory()->create(['project_id' => $project->id]);

        // Touch every Project relation.
        $this->assertInstanceOf(Customer::class, $project->customer);
        $this->assertNotEmpty($project->folders);
        $this->assertNotEmpty($project->topLevelFolders);
        $this->assertNotEmpty($project->documents);       // hasManyThrough
        $this->assertNotEmpty($project->contractors);     // belongsToMany
        $this->assertNotEmpty($project->auditLogs);
        $this->assertIsBool($project->isComplete());
    }

    public function test_project_folder_relations_and_top_level_walk(): void
    {
        $top = ProjectFolder::factory()->topLevel()->create();
        $child = ProjectFolder::factory()->child($top)->create();
        FolderPermission::factory()->create(['project_folder_id' => $top->id]);
        ProjectDocument::factory()->create(['project_folder_id' => $child->id]);

        $this->assertInstanceOf(Project::class, $child->project);
        $this->assertInstanceOf(ProjectFolder::class, $child->parent);
        $this->assertNotEmpty($top->children);
        $this->assertNotEmpty($child->documents);
        $this->assertNotEmpty($top->permissions);

        // topLevelFolder() walks up to the top-level ancestor.
        $this->assertTrue($child->topLevelFolder()->is($top));
        $this->assertTrue($top->topLevelFolder()->is($top));
    }

    public function test_folder_permission_and_document_relations_resolve(): void
    {
        $permission = FolderPermission::factory()->create();
        $this->assertInstanceOf(ProjectFolder::class, $permission->folder);

        $document = ProjectDocument::factory()->create();
        $this->assertInstanceOf(ProjectFolder::class, $document->folder);
        // uploader is nullable; touching the relation must not error.
        $document->uploader;

        // Property 18 guard: no public URL accessor exists on ProjectDocument.
        $this->assertFalse(array_key_exists('url', $document->toArray()));
    }

    public function test_project_contractor_relations_resolve(): void
    {
        $pc = ProjectContractor::factory()->create();

        $this->assertInstanceOf(Project::class, $pc->project);
        $this->assertInstanceOf(Contractor::class, $pc->contractor);
        // assignedBy is nullable; touching the relation must not error.
        $pc->assignedBy;
    }

    public function test_document_audit_log_relations_resolve(): void
    {
        $folder = ProjectFolder::factory()->topLevel()->create();
        $document = ProjectDocument::factory()->create(['project_folder_id' => $folder->id]);

        $log = DocumentAuditLog::factory()->create([
            'project_id' => $folder->project_id,
            'project_document_id' => $document->id,
            'project_folder_id' => $folder->id,
        ]);

        $this->assertInstanceOf(Project::class, $log->project);
        $this->assertInstanceOf(ProjectDocument::class, $log->document);
        $this->assertInstanceOf(ProjectFolder::class, $log->folder);
        // user is nullable; touching the relation must not error.
        $log->user;
    }

    public function test_folder_template_casts_and_default_template(): void
    {
        $template = FolderTemplate::factory()->create();

        $this->assertIsArray($template->subfolders);
        $this->assertIsArray($template->permissions);
        $this->assertCount(5, FolderTemplate::DEFAULT_TEMPLATE);
    }

    public function test_customer_invitation_relations_resolve(): void
    {
        $customer = $this->makeCustomer();
        $contact = $this->makeCustomerContact($customer);
        $inviter = User::factory()->create();
        $user = User::factory()->create(['customer_id' => $customer->id]);

        $invitation = CustomerInvitation::factory()->create([
            'customer_id' => $customer->id,
            'customer_contact_id' => $contact->id,
            'invited_by_user_id' => $inviter->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Customer::class, $invitation->customer);
        $this->assertInstanceOf(User::class, $invitation->invitedBy);
        $this->assertInstanceOf(User::class, $invitation->user);
    }
}
