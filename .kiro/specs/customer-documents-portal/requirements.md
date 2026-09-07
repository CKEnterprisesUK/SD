# Requirements Document

## Introduction

The Customer Documents Portal adds a SharePoint-style document library to the SiteDesk platform, enabling Admins to manage construction Projects for Customers and to share documents securely with Customers and Contractors. Each Project is created from a configurable master folder template and progresses through defined lifecycle states. Documents are stored on a private disk and served only through authenticated, permission-checked routes. The feature introduces a `customer` user role with an invite flow that mirrors the existing Contractor invite flow, reuses existing Contractor accounts scoped to assigned Projects, and integrates with the established Admin routing and settings patterns.

## Glossary

- **SiteDesk**: The overall Laravel application (Laravel 13, PHP 8.3) that hosts this feature.
- **Admin**: A User whose role is `admin`, holding full read/write access to all Projects, folders, documents, and settings.
- **Customer**: A business or individual represented by the existing Customer model, to whom a Project belongs.
- **Customer_User**: A User whose role is `customer`, granted login access to view Projects belonging to their associated Customer.
- **Contractor**: A User whose role is `contractor`, represented by the existing Contractor model, granted access scoped to assigned Projects.
- **Project**: An entity created by an Admin for a Customer, containing one document library seeded from the Master_Template, and holding a lifecycle Project_State.
- **Project_State**: The lifecycle status of a Project, one of `Draft`, `Planning`, `Active`, or `Complete`.
- **Master_Template**: The admin-configurable definition of top-level folders, their order, and their default permissions used to seed new Projects.
- **Top_Level_Folder**: One of the folders defined at the root of a Project document library (for example "Planning and Design docs").
- **Subfolder**: Any folder nested beneath a Top_Level_Folder or another Subfolder within a Project document library.
- **Folder**: A Top_Level_Folder or a Subfolder within a Project document library.
- **Document**: An uploaded file stored within a Folder in a Project document library.
- **Permission_Level**: The access granted to a role for a Folder, one of `read-write`, `read-only`, or `no-access`.
- **Private_Disk**: The Laravel `local` filesystem disk rooted at `storage/app/private`, used to store Documents without public URLs.
- **Document_Serving_Route**: The authenticated, permission-checked controller route that streams a Document to a requesting user.
- **Audit_Log**: The persistent record of document and folder operations, including actor, action, target, and timestamp.
- **Contractor_Invitation**: The existing invitation mechanism used to onboard Contractors, reused as the pattern for Customer_User invitations.

## Requirements

### Requirement 1: Project Management

**User Story:** As an Admin, I want to create and manage Projects for Customers with defined lifecycle states, so that each Customer engagement has a structured, trackable document workspace.

#### Acceptance Criteria

1. WHEN an Admin submits a valid new Project for a Customer, THE SiteDesk SHALL create the Project with Project_State set to `Draft`.
2. WHEN an Admin creates a Project, THE SiteDesk SHALL associate the Project with exactly one Customer.
3. WHEN an Admin changes a Project_State, THE SiteDesk SHALL persist the new Project_State selected from `Draft`, `Planning`, `Active`, or `Complete`.
4. THE SiteDesk SHALL restrict Project creation and Project_State changes to Admin users.
5. IF a non-Admin user requests Project creation or Project_State change, THEN THE SiteDesk SHALL deny the request and return an authorization error.
6. WHEN an Admin views the Project list, THE SiteDesk SHALL display each Project with its associated Customer and current Project_State.

### Requirement 2: Read-Only Completed Projects

**User Story:** As an Admin, I want completed Projects to become read-only, so that finalized document libraries are preserved without further modification.

#### Acceptance Criteria

1. WHILE a Project has Project_State set to `Complete`, THE SiteDesk SHALL reject requests to upload, delete, copy, or move Documents within that Project.
2. WHILE a Project has Project_State set to `Complete`, THE SiteDesk SHALL reject requests to create, rename, reorder, or delete Folders within that Project.
3. WHILE a Project has Project_State set to `Complete`, THE SiteDesk SHALL permit reading and downloading of Documents to users holding at least `read-only` Permission_Level for the containing Folder.
4. IF a user submits a modifying operation to a Project with Project_State set to `Complete`, THEN THE SiteDesk SHALL deny the operation and return a read-only error.

### Requirement 3: Customer Role and Invitation

**User Story:** As an Admin, I want to invite Customers to log in with their own accounts, so that Customers can securely access the Projects that belong to them.

#### Acceptance Criteria

1. THE SiteDesk SHALL support a User role value of `customer`.
2. WHEN an Admin sends an invitation to a Customer contact, THE SiteDesk SHALL create a pending Customer_User invitation following the Contractor_Invitation pattern.
3. WHEN a Customer contact accepts an invitation and sets a password, THE SiteDesk SHALL create a User with role `customer` associated with the corresponding Customer.
4. WHEN a Customer_User invitation is sent, THE SiteDesk SHALL deliver the invitation using the SiteDeskResetPasswordNotification mechanism used for Contractor onboarding.
5. WHILE a Customer_User is authenticated, THE SiteDesk SHALL grant access only to Projects associated with the Customer of that Customer_User.
6. IF a Customer_User requests a Project associated with a different Customer, THEN THE SiteDesk SHALL deny the request and return an authorization error.
7. WHILE a Contractor is authenticated, THE SiteDesk SHALL grant access only to Projects to which the Contractor is assigned.

### Requirement 4: Master Folder Template Settings

**User Story:** As an Admin, I want to configure the master folder template and its permissions from a settings screen, so that new Projects are seeded with a consistent, organization-defined structure.

#### Acceptance Criteria

1. THE SiteDesk SHALL provide an Admin settings screen to add, remove, and reorder Top_Level_Folders in the Master_Template.
2. THE SiteDesk SHALL allow an Admin to set the Permission_Level for the `admin`, `contractor`, and `customer` roles on each Top_Level_Folder in the Master_Template.
3. WHEN an Admin saves changes to the Master_Template, THE SiteDesk SHALL persist the updated Top_Level_Folder set, order, and Permission_Levels.
4. THE SiteDesk SHALL restrict Master_Template configuration to Admin users following the settings routing pattern used by PortalSettingsController.
5. WHEN a new Project is created, THE SiteDesk SHALL seed the Project document library from the current Master_Template, including Top_Level_Folders, their order, their Subfolders, and their Permission_Levels.
6. WHERE the Master_Template defines the default structure, THE SiteDesk SHALL initialize it with the five Top_Level_Folders: `Planning and Design docs`, `Quotes`, `Build Stage`, `Health and Safety`, and `SiteDesk Admin Only`.
7. WHERE a Top_Level_Folder is initialized from defaults, THE SiteDesk SHALL create its default Subfolders as specified for that Top_Level_Folder.
8. WHERE the `Planning and Design docs` Top_Level_Folder is initialized from defaults, THE SiteDesk SHALL set Permission_Level `read-write` for `admin` and `read-only` for `contractor` and `customer`.
9. WHERE the `Quotes` Top_Level_Folder is initialized from defaults, THE SiteDesk SHALL set Permission_Level `read-write` for `admin`, `read-only` for `customer`, and `no-access` for `contractor`.
10. WHERE the `Build Stage` Top_Level_Folder is initialized from defaults, THE SiteDesk SHALL set Permission_Level `read-write` for `admin` and `read-only` for `contractor` and `customer`.
11. WHERE the `Health and Safety` Top_Level_Folder is initialized from defaults, THE SiteDesk SHALL set Permission_Level `read-write` for `admin` and `read-only` for `contractor` and `customer`.
12. WHERE the `SiteDesk Admin Only` Top_Level_Folder is initialized from defaults, THE SiteDesk SHALL set Permission_Level `read-write` for `admin` and `no-access` for `contractor` and `customer`.

### Requirement 5: Per-Project Folder Management

**User Story:** As an Admin, I want to manage the folder structure and permissions within an individual Project, so that I can tailor a Project's document library beyond the seeded template.

#### Acceptance Criteria

1. WHILE a Project has Project_State other than `Complete`, THE SiteDesk SHALL allow an Admin to add, rename, reorder, and remove Top_Level_Folders within that Project.
2. WHILE a Project has Project_State other than `Complete`, THE SiteDesk SHALL allow an Admin to set the Permission_Level for the `admin`, `contractor`, and `customer` roles on each Top_Level_Folder within that Project.
3. WHILE a Project has Project_State other than `Complete`, THE SiteDesk SHALL allow an Admin to create Subfolders within any Folder in that Project.
4. THE SiteDesk SHALL apply the Permission_Level of a Top_Level_Folder to all Subfolders nested beneath that Top_Level_Folder.
5. THE SiteDesk SHALL restrict per-Project Folder management to Admin users.

### Requirement 6: Document Library Browsing

**User Story:** As an authorized user, I want to browse the folders and documents of a Project I have access to, so that I can find and review relevant files.

#### Acceptance Criteria

1. WHEN an authorized user opens a Project document library, THE SiteDesk SHALL display the Folders for which the user holds at least `read-only` Permission_Level.
2. WHEN an authorized user opens a Folder, THE SiteDesk SHALL display the Subfolders and Documents contained within that Folder.
3. WHERE a role holds Permission_Level `no-access` for a Top_Level_Folder, THE SiteDesk SHALL exclude that Top_Level_Folder and its Subfolders and Documents from the view for users of that role.
4. IF a user requests a Folder for which the user holds Permission_Level `no-access`, THEN THE SiteDesk SHALL deny the request and return an authorization error.

### Requirement 7: Document Upload and File Type Support

**User Story:** As an authorized user, I want to upload documents into folders I can write to, so that project files are stored in the correct location.

#### Acceptance Criteria

1. WHEN a user with `read-write` Permission_Level for a Folder uploads a Document to that Folder, THE SiteDesk SHALL store the Document on the Private_Disk and record it within that Folder.
2. THE SiteDesk SHALL accept Document uploads of file types including `.docx`, `.pdf`, and common image formats.
3. IF a user without `read-write` Permission_Level for a Folder attempts to upload a Document to that Folder, THEN THE SiteDesk SHALL deny the upload and return an authorization error.
4. IF an uploaded file exceeds the configured maximum size or is of a disallowed file type, THEN THE SiteDesk SHALL reject the upload and return a validation error.

### Requirement 8: Secure Document Storage and Serving

**User Story:** As a security-conscious Admin, I want documents stored privately and served only through authenticated checks, so that files cannot be accessed via public URLs.

#### Acceptance Criteria

1. THE SiteDesk SHALL store every Document on the Private_Disk located at `storage/app/private`.
2. THE SiteDesk SHALL serve every Document exclusively through the Document_Serving_Route.
3. WHEN an authenticated user requests a Document through the Document_Serving_Route and holds at least `read-only` Permission_Level for the containing Folder, THE SiteDesk SHALL stream the Document to the user.
4. IF an unauthenticated request is made for a Document, THEN THE SiteDesk SHALL deny the request and return an authentication error.
5. IF an authenticated user requests a Document for which the user holds Permission_Level `no-access` on the containing Folder, THEN THE SiteDesk SHALL deny the request and return an authorization error.
6. THE SiteDesk SHALL prevent generation of publicly accessible URLs for Documents.

### Requirement 9: Document Operations

**User Story:** As an authorized user, I want to download, delete, copy, and organize documents and folders, so that I can maintain the project document library.

#### Acceptance Criteria

1. WHEN a user with at least `read-only` Permission_Level for a Folder requests to download a Document in that Folder, THE SiteDesk SHALL stream the Document through the Document_Serving_Route.
2. WHEN a user with `read-write` Permission_Level for a Folder requests to delete a Document in that Folder, THE SiteDesk SHALL remove the Document from the Folder and delete the file from the Private_Disk.
3. WHEN a user with `read-write` Permission_Level for a destination Folder requests to copy a Document into that Folder, THE SiteDesk SHALL create a copy of the Document in the destination Folder on the Private_Disk.
4. WHEN a user with `read-write` Permission_Level for a Folder requests to create a Subfolder within that Folder, THE SiteDesk SHALL create the Subfolder and apply the Permission_Level of the containing Top_Level_Folder.
5. IF a user without `read-write` Permission_Level for a Folder requests to delete a Document, copy a Document into that Folder, or create a Subfolder within that Folder, THEN THE SiteDesk SHALL deny the request and return an authorization error.

### Requirement 10: Audit Log

**User Story:** As an Admin, I want an audit log of document and folder operations, so that I can review who performed which actions and when.

#### Acceptance Criteria

1. WHEN a Document is uploaded, downloaded, deleted, or copied, THE SiteDesk SHALL record an Audit_Log entry containing the acting user, the action, the target Document, the containing Folder, and the timestamp.
2. WHEN a Folder is created, renamed, reordered, or deleted, THE SiteDesk SHALL record an Audit_Log entry containing the acting user, the action, the target Folder, and the timestamp.
3. WHEN an Admin views the Audit_Log for a Project, THE SiteDesk SHALL display the recorded entries for that Project.
4. THE SiteDesk SHALL restrict Audit_Log viewing to Admin users.
