# Implementation Plan: Customer Documents Portal

## Overview

Build the SharePoint-style, permission-controlled document library in SiteDesk (Laravel 13, PHP 8.3) by layering the work bottom-up: schema first (migrations), then Eloquent models and relationships, then domain services (permission resolution, template seeding, private storage, audit logging), then authorization (policies + read-only middleware), then controllers/routes, then Blade views. Property-based and feature tests accompany each implementation slice, covering all 23 correctness properties from the design. Documents are stored only on the private `local` disk and served exclusively through an authenticated, permission-checked streaming route.

Test infrastructure notes:
- Feature tests use `RefreshDatabase`, `Storage::fake('local')`, and `Notification::fake()`.
- Property tests loop over >=100 randomized cases each, tagged `Feature: customer-documents-portal, Property {n}: {text}`.
- Model factories are created alongside each model to support randomized generation.

## Tasks

- [x] 1. Create database migrations for schema foundation
  - [x] 1.1 Add `customer_id` to users and create `folder_templates` migration
    - Write `..._add_customer_id_to_users_table.php` adding nullable `customer_id` foreign key (constrained `customers`, `nullOnDelete`) after `status`, with an index
    - Write `..._create_folder_templates_table.php` with `name`, `sort_order`, `subfolders` (json), `permissions` (json), timestamps, index on `sort_order`
    - _Requirements: 3.1, 4.1, 4.2, 4.3_

  - [x] 1.2 Create `projects` and `project_folders` migrations
    - Write `..._create_projects_table.php` with `customer_id` (constrained, cascade), `created_by_user_id` (nullable), `name`, `reference` (nullable), `state` default `Draft`, `description` (nullable), timestamps, index `[customer_id, state]`
    - Write `..._create_project_folders_table.php` self-referential tree: `project_id`, nullable `parent_id` (constrained self, cascade), `name`, `is_top_level` default false, `sort_order`, timestamps, index `[project_id, parent_id, sort_order]`
    - _Requirements: 1.1, 1.2, 1.3, 4.5, 5.1, 5.3_

  - [x] 1.3 Create `folder_permissions`, `project_documents`, and `project_contractors` migrations
    - Write `..._create_folder_permissions_table.php`: `project_folder_id` (constrained, cascade), `role`, `level`, timestamps, unique `[project_folder_id, role]`
    - Write `..._create_project_documents_table.php`: `project_folder_id` (constrained, cascade), `uploaded_by_user_id` (nullable), `original_name`, `storage_path`, `mime_type` (nullable), `size_bytes` default 0, timestamps, index `project_folder_id`
    - Write `..._create_project_contractors_table.php`: `project_id`, `contractor_id` (constrained, cascade), `assigned_by_user_id` (nullable), timestamps, unique `[project_id, contractor_id]`
    - _Requirements: 4.2, 7.1, 8.1, 3.7_

  - [x] 1.4 Create `document_audit_logs` and `customer_invitations` migrations
    - Write `..._create_document_audit_logs_table.php`: `project_id`, `user_id` (nullable), `action`, `target_type`, nullable `project_document_id`, nullable `project_folder_id`, `metadata` (json nullable), `created_at` useCurrent, index `[project_id, created_at]`
    - Write `..._create_customer_invitations_table.php`: `customer_id` (constrained, cascade), nullable `customer_contact_id`, `email`, nullable `invited_by_user_id`, nullable `user_id`, nullable `accepted_at`, timestamps, index `[customer_id, email]`
    - _Requirements: 10.1, 10.2, 3.2_

- [x] 2. Implement Eloquent models and relationships
  - [x] 2.1 Extend User model with customer role support
    - Add `customer_id` to `$fillable`, add `customer(): BelongsTo`, add `isCustomer(): bool` (`role === 'customer'`)
    - _Requirements: 3.1, 3.5_

  - [x] 2.2 Create Project model and factory
    - `$fillable`, `STATES` const, relations `customer`, `folders`, `topLevelFolders` (`is_top_level`, ordered by `sort_order`), `documents` (hasManyThrough), `contractors` (belongsToMany via `project_contractors`), `auditLogs`; add `isComplete(): bool`
    - Create `ProjectFactory` (random state, linked customer)
    - _Requirements: 1.1, 1.2, 1.3, 2.1, 3.7_

  - [x] 2.3 Create ProjectFolder model and factory
    - `$fillable`, cast `is_top_level` boolean, relations `project`, `parent`, `children` (ordered), `documents`, `permissions`; implement `topLevelFolder(): self` that walks up parents to the top-level ancestor
    - Create `ProjectFolderFactory` supporting top-level and child folders
    - _Requirements: 4.5, 5.1, 5.3, 5.4, 9.4_

  - [x] 2.4 Create FolderPermission, ProjectDocument, ProjectContractor models and factories
    - `FolderPermission`: `$fillable` (`project_folder_id`, `role`, `level`), `folder` relation
    - `ProjectDocument`: `$fillable`, relations `folder`, `uploader`; explicitly NO URL accessor
    - `ProjectContractor`: pivot-style model with `project`, `contractor`, `assignedBy` relations
    - Create matching factories
    - _Requirements: 4.2, 7.1, 8.1, 8.6, 3.7_

  - [x] 2.5 Create DocumentAuditLog, FolderTemplate, CustomerInvitation models and factories
    - `DocumentAuditLog`: `$fillable`, `metadata` json cast, `$timestamps=false` semantics with `created_at`, relations `project`, `user`, `document`, `folder`
    - `FolderTemplate`: `$fillable`, `subfolders`/`permissions` json casts, `DEFAULT_TEMPLATE` const holding the five default folders per design
    - `CustomerInvitation`: `$fillable`, `accepted_at` datetime cast, relations `customer`, `invitedBy`, `user`
    - Create matching factories
    - _Requirements: 10.1, 10.2, 4.6, 3.2_

- [x] 3. Checkpoint - migrate and verify models
  - Run migrations against the test database and instantiate each model relation. Ensure all tests pass, ask the user if questions arise.

- [x] 4. Implement master folder template seeding
  - [x] 4.1 Create FolderTemplateSeeder with default structure
    - Seed the five top-level folders in order with default subfolders and per-role permissions from `FolderTemplate::DEFAULT_TEMPLATE` (Planning and Design docs, Quotes, Build Stage, Health and Safety, SiteDesk Admin Only)
    - _Requirements: 4.6, 4.7, 4.8, 4.9, 4.10, 4.11, 4.12_

  - [x] 4.2 Write property test for default seeder structure
    - **Property 10 (default case): Project libraries seeded faithfully — validates the five named folders and their default permissions per 4.8-4.12**
    - **Validates: Requirements 4.6, 4.7, 4.8, 4.9, 4.10, 4.11, 4.12**

  - [x] 4.3 Implement FolderTemplateService (all/sync)
    - `all()` returns ordered `FolderTemplate` rows; `sync(array $folders)` replaces the master template (set, order, permissions) transactionally
    - _Requirements: 4.1, 4.2, 4.3_

  - [x] 4.4 Write property test for template save round-trip
    - **Property 8: Master template save round-trips — random set/order/permission levels saved then reloaded are identical**
    - **Validates: Requirements 4.2, 4.3**

  - [x] 4.5 Implement ProjectSeeder service
    - `seed(Project $project)` inside a DB transaction: for each ordered template row create a top-level `ProjectFolder` (`is_top_level=true`, `sort_order`), create `FolderPermission` rows per role, then create child `ProjectFolder` rows for each subfolder (no permission rows on subfolders)
    - _Requirements: 4.5_

  - [x] 4.6 Write property test for template-to-project seeding
    - **Property 10: For any master template, creating a project produces a library whose top-level folders, order, subfolders, and per-role permissions match the template**
    - **Validates: Requirements 4.5**

- [x] 5. Implement PermissionResolver service
  - [x] 5.1 Implement PermissionResolver core methods
    - `level(User, ProjectFolder): string` — admin => read-write; else project-scope check (customer owns / contractor assigned) else no-access; resolve via `folder.topLevelFolder()` + `FolderPermission` for the role
    - `canRead` (level !== no-access), `canWrite` (level === read-write), `visibleTopLevelFolders(User, Project): Collection`
    - _Requirements: 3.5, 3.6, 3.7, 5.4, 6.1, 6.3, 9.4_

  - [x] 5.2 Write property test for subfolder permission inheritance
    - **Property 11: For any folder tree and subfolder, resolved level equals the top-level ancestor's level; new subfolders resolve to the same level**
    - **Validates: Requirements 5.4, 9.4**

  - [x] 5.3 Write property test for project-scope access
    - **Property 7: Customer access iff `project.customer_id == user.customer_id`; contractor access iff assigned to the project**
    - **Validates: Requirements 3.5, 3.6, 3.7**

  - [x] 5.4 Write property test for browsable folder set
    - **Property 13: Browsable set equals folders with resolved level !== no-access; a no-access top-level folder excludes its whole subtree**
    - **Validates: Requirements 6.1, 6.3, 6.4**

- [x] 6. Implement storage and audit services
  - [x] 6.1 Implement AuditLogger service
    - Static helpers to record `document_audit_logs` entries for uploaded/downloaded/deleted/copied documents and created/renamed/reordered/deleted folders, capturing actor, action, target document/folder, project, timestamp, metadata
    - _Requirements: 10.1, 10.2_

  - [x] 6.2 Implement DocumentStorageService (private disk)
    - `store(UploadedFile, ProjectFolder)` writes to `local` disk under `projects/{project}/{folder}/{ulid}.{ext}` and creates a `ProjectDocument`; `copy(ProjectDocument, destinationFolder)` duplicates file + record; `delete(ProjectDocument)` removes file and record; each op calls `AuditLogger`
    - _Requirements: 7.1, 8.1, 9.2, 9.3_

  - [x] 6.3 Write property test for private upload storage
    - **Property 14: Uploading stores the file on the private disk and creates a folder-linked Document; every stored Document resides on the private disk**
    - **Validates: Requirements 7.1, 8.1**

  - [x] 6.4 Write property test for delete and copy
    - **Property 19: Delete removes both record and private file**
    - **Property 20: Copy creates a new record + new private file with identical contents, original unchanged**
    - **Validates: Requirements 9.2, 9.3**

  - [x] 6.5 Write property test for no public URL
    - **Property 18: No public URL is generated for any document; files reside only on the private disk (assert no URL accessor on ProjectDocument)**
    - **Validates: Requirements 8.2, 8.6**

- [x] 7. Checkpoint - services green
  - Ensure all service-level tests pass, ask the user if questions arise.

- [x] 8. Implement authorization policies and read-only middleware
  - [x] 8.1 Implement ProjectPolicy, FolderPolicy, DocumentPolicy
    - `ProjectPolicy`: `create`/`update`/`changeState`/`manage`/`viewAudit` admin-only; `view` admin or owning customer or assigned contractor
    - `FolderPolicy`: `view` => `canRead`; `manage` admin-only + not Complete; `createSubfolder` => `canWrite` + not Complete
    - `DocumentPolicy`: `view`/`download` => `canRead`; `upload`/`delete`/`copy` => `canWrite` + not Complete
    - Register policies in `AppServiceProvider::boot()`
    - _Requirements: 1.4, 1.5, 4.4, 5.5, 6.4, 7.3, 8.5, 9.5, 10.4_

  - [x] 8.2 Implement EnsureProjectWritable middleware
    - Resolve `Project` from route; abort 403 read-only error when `state === 'Complete'`; register in HTTP kernel/route alias
    - _Requirements: 2.1, 2.2, 2.4_

  - [x] 8.3 Write feature/property test for admin-only management
    - **Property 3: Non-admin project create/state-change denied**
    - **Property 12: Per-project folder management admin-only and blocked when Complete**
    - **Property 23: Audit log viewing admin-only**
    - **Validates: Requirements 1.4, 1.5, 5.1, 5.2, 5.5, 10.4**

  - [x] 8.4 Write property test for read-only enforcement
    - **Property 4: Complete projects reject all modifying document/folder operations**
    - **Property 5: Complete projects still allow reading/downloading**
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4**

- [x] 9. Implement Project management controller and routes
  - [x] 9.1 Implement ProjectController (CRUD) and routes
    - `index`, `create`, `store` (authorize create, `state=Draft`, run `ProjectSeeder::seed`), `show`, `edit`, `update`; register under `admin` prefix / `admin.` name group with route-model binding
    - _Requirements: 1.1, 1.2, 1.6, 4.5_

  - [x] 9.2 Implement project state change and contractor assignment
    - `updateState` (authorize `changeState`, validate against `Project::STATES`), contractor assign/unassign endpoints writing `project_contractors`
    - _Requirements: 1.3, 1.4, 1.5, 3.7_

  - [x] 9.3 Write property test for project creation and state changes
    - **Property 1: New projects start in Draft with exactly one customer**
    - **Property 2: State changes persist the selected valid state**
    - **Validates: Requirements 1.1, 1.2, 1.3**

- [x] 10. Implement per-project folder management controller and routes
  - [x] 10.1 Implement ProjectFolderController and routes
    - `store` (top-level/sub), `update` (rename), `reorder`, `destroy`, `permissions.update` (top-level only); routes under `admin` + `EnsureProjectWritable`, authorized via `FolderPolicy`; new subfolders inherit top-level permission at resolution time; each op calls `AuditLogger`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 9.4, 10.2_

  - [x] 10.2 Write feature test for folder operation auditing
    - **Property 22: Folder create/rename/reorder/delete each records an audit entry with actor/action/target/timestamp**
    - **Validates: Requirements 10.2**

- [x] 11. Implement master folder template settings screen
  - [x] 11.1 Implement FolderTemplateSettingsController and settings routes
    - `edit`/`update` under `admin.settings.folder-template.*` (inline admin check), delegating persistence to `FolderTemplateService::sync`; add a card to `SettingsHubController`/settings index
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

  - [x] 11.2 Write feature test for settings admin-only access
    - **Property 9: Non-admin master template configuration denied with authorization error**
    - **Validates: Requirements 4.4**

- [x] 12. Implement document browsing, serving, and operations
  - [x] 12.1 Implement ProjectLibraryController (browsing)
    - `show` (permission-filtered top-level tree via `PermissionResolver::visibleTopLevelFolders`), `folder` (subfolders + documents, deny direct no-access request); routes under `auth` reachable by admin/customer/contractor
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [x] 12.2 Implement DocumentServeController (secure streaming)
    - `show` authorizes `download` (canRead), streams from `local` disk, 404 if key absent, records download audit; only route producing document bytes
    - _Requirements: 8.2, 8.3, 8.4, 8.5, 9.1_

  - [x] 12.3 Implement document upload/delete/copy controller actions and routes
    - Upload (validate size/type incl. `.docx`/`.pdf`/images), delete, copy — via `DocumentStorageService`, authorized via `DocumentPolicy`, wrapped by `EnsureProjectWritable`
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 9.2, 9.3, 9.5_

  - [x] 12.4 Write property test for secure serving
    - **Property 17: Unauthenticated => auth error; no-access => 403; >=read-only => exact stored bytes**
    - **Validates: Requirements 8.3, 8.4, 8.5, 9.1**

  - [x] 12.5 Write property test for upload permission and validation
    - **Property 15: Non-write users denied upload/delete/copy/subfolder-create**
    - **Property 16: Oversized/disallowed uploads rejected with validation error, no file written**
    - **Validates: Requirements 7.3, 7.4, 9.5**

  - [x] 12.6 Write feature test for document operation auditing
    - **Property 21: Upload/download/delete/copy each records an audit entry with actor/action/target document/folder/timestamp**
    - **Validates: Requirements 10.1**

- [x] 13. Implement audit log viewing and customer invite flow
  - [x] 13.1 Implement ProjectAuditLogController and route
    - `index` (admin-only inline check) displaying entries for the project; route under `admin`
    - _Requirements: 10.3, 10.4_

  - [x] 13.2 Implement CustomerInviteController (invite flow)
    - `send` mirrors `ContractorController@sendInvite`: create/find `customer`-role User linked via `customer_id`, create `CustomerInvitation`, dispatch `Password::sendResetLink` (delivers `SiteDeskResetPasswordNotification`)
    - _Requirements: 3.2, 3.3, 3.4_

  - [x] 13.3 Write feature test for the invite flow
    - **Property 6: Invite creates a pending customer invitation; on acceptance the resulting User has role `customer` linked to the Customer (assert Notification::fake reset link sent)**
    - **Validates: Requirements 3.2, 3.3**

- [ ] 14. Implement Blade views
  - [x] 14.1 Implement admin project views
    - `admin/projects/index` (project + customer + state), `create`, `edit`, `show` (library tree, contractor assignment, audit-log link) extending `layouts.app`
    - _Requirements: 1.6, 6.2_

  - [-] 14.2 Implement folder management and settings views
    - `admin/projects/folders/*` partials (create/rename/reorder + per-role permission matrix); `admin/settings/folder-template.blade.php` master template editor reached from settings hub
    - _Requirements: 4.1, 4.2, 5.1, 5.2_

  - [-] 14.3 Implement audit log and portal views
    - `admin/projects/audit-log.blade.php` (actor/action/target/timestamp); `portal/projects/index` and `portal/library` (permission-filtered tree, upload forms for read-write folders, download links to `documents.serve`)
    - _Requirements: 6.1, 6.2, 7.1, 9.1, 10.3_

- [~] 15. Final checkpoint - full suite green
  - Run migrations + seeders and the full feature/property test suite. Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional test tasks and can be skipped for a faster MVP, but they cover the 23 correctness properties and are strongly recommended.
- Each task references specific requirement sub-clauses for traceability.
- Foundational data/model/service work (tasks 1-8) precedes controllers/routes (9-13) and views (14), matching the design's dependency direction.
- Property tests loop over >=100 randomized cases and are tagged `Feature: customer-documents-portal, Property {n}: {text}`; feature tests use `RefreshDatabase`, `Storage::fake('local')`, and `Notification::fake()`.
- Documents are never placed on the public disk and `ProjectDocument` exposes no URL accessor, so no `asset()`/public URL can be produced.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "1.4"] },
    { "id": 1, "tasks": ["2.1", "2.2", "2.3", "2.4", "2.5"] },
    { "id": 2, "tasks": ["4.1", "4.3", "4.5", "5.1", "6.1"] },
    { "id": 3, "tasks": ["4.2", "4.4", "4.6", "5.2", "5.3", "5.4", "6.2"] },
    { "id": 4, "tasks": ["6.3", "6.4", "6.5", "8.1", "8.2"] },
    { "id": 5, "tasks": ["8.3", "8.4", "9.1", "10.1", "11.1", "13.1", "13.2"] },
    { "id": 6, "tasks": ["9.2", "9.3", "10.2", "11.2", "12.1", "12.2", "12.3", "13.3"] },
    { "id": 7, "tasks": ["12.4", "12.5", "12.6", "14.1", "14.2", "14.3"] }
  ]
}
```
