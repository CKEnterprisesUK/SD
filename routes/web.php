<?php

use App\Http\Controllers\Admin\ContractorController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerInviteController;
use App\Http\Controllers\Admin\FolderTemplateSettingsController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\PortalSettingsController;
use App\Http\Controllers\Admin\ProjectAuditLogController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProjectDocumentController;
use App\Http\Controllers\Admin\ProjectFolderController;
use App\Http\Controllers\Admin\PricingSettingsController;
use App\Http\Controllers\Admin\QuoteAiController;
use App\Http\Controllers\Admin\QuoteAiDraftController;
use App\Http\Controllers\Admin\QuoteController;
use App\Http\Controllers\Admin\QuoteFileController;
use App\Http\Controllers\Admin\QuoteFollowUpController;
use App\Http\Controllers\Admin\QuoteLineItemController;
use App\Http\Controllers\Admin\QuoteNoteController;
use App\Http\Controllers\Contractor\InvoiceController as ContractorInvoiceController;
use App\Http\Controllers\Contractor\ProjectController as ContractorProjectController;
use App\Http\Controllers\Customer\ProjectController as CustomerProjectController;
use App\Http\Controllers\DocumentServeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectLibraryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\QuotePackController;
use App\Http\Controllers\Admin\QuotePackTemplateController;
use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\Admin\SettingsHubController;
use App\Http\Controllers\Profile\ContractorInvoiceDetailsController;
use App\Http\Controllers\Admin\UserController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Legal pages (public)
|--------------------------------------------------------------------------
*/

Route::view('/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/cookies', 'legal.cookies')->name('legal.cookies');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

        Route::patch('/profile/contractor-invoice-details', [ContractorInvoiceDetailsController::class, 'update'])
    ->name('profile.contractor-invoice-details.update');

    Route::get('/profile/contractor-invoice-details', function () {
    return redirect()->route('profile.edit');
})->name('profile.contractor-invoice-details.edit');


    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Contractor invoice routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])
    ->prefix('my-invoices')
    ->name('contractor.invoices.')
    ->group(function () {
        Route::get('/', [ContractorInvoiceController::class, 'index'])
            ->name('index');

        Route::get('/create', [ContractorInvoiceController::class, 'create'])
            ->name('create');

        Route::post('/', [ContractorInvoiceController::class, 'store'])
            ->name('store');

        Route::get('/{invoice}/download', [ContractorInvoiceController::class, 'download'])
            ->name('download');

        Route::get('/{invoice}/edit', [ContractorInvoiceController::class, 'edit'])
            ->name('edit');

        Route::put('/{invoice}', [ContractorInvoiceController::class, 'update'])
            ->name('update');

        Route::get('/{invoice}', [ContractorInvoiceController::class, 'show'])
            ->name('show');
    });

/*
|--------------------------------------------------------------------------
| Contractor projects ("My Projects")
|--------------------------------------------------------------------------
| Contractor-facing list + detail for assigned projects. Lives under `auth`
| (NOT the admin group). Access is gated by isContractor() + ProjectPolicy@view
| (assigned contractors only). Views expose only name/state and the derived
| customer address — never quotes or full customer details.
*/

Route::middleware(['auth'])
    ->prefix('my-projects')
    ->name('contractor.projects.')
    ->group(function () {
        Route::get('/', [ContractorProjectController::class, 'index'])
            ->name('index');

        Route::get('/{project}', [ContractorProjectController::class, 'show'])
            ->name('show');
    });

/*
|--------------------------------------------------------------------------
| Customer projects ("My Projects")
|--------------------------------------------------------------------------
| Customer-facing list of the projects belonging to the customer's linked
| customer record. Lives under `auth` (NOT the admin group). Access is gated
| by isCustomer() + a customer_id scope; each project opens into the shared
| permission-filtered document library (projects.library).
*/

Route::middleware(['auth'])
    ->prefix('portal/projects')
    ->name('customer.projects.')
    ->group(function () {
        Route::get('/', [CustomerProjectController::class, 'index'])
            ->name('index');
    });

/*
|--------------------------------------------------------------------------
| Documents (admin + customer + contractor)
|--------------------------------------------------------------------------
| Secure document streaming reachable by any authenticated role. Access is
| gated per-document by DocumentPolicy@download (PermissionResolver::canRead),
| so this route lives under `auth` but NOT under the `admin.` group.
*/

Route::middleware(['auth'])->group(function () {
    // Permission-filtered library browsing, reachable by admin/customer/contractor.
    // Access is gated per action by ProjectPolicy@view / FolderPolicy@view.
    Route::get('/projects/{project}/library', [ProjectLibraryController::class, 'show'])
        ->name('projects.library');

    Route::get('/projects/{project}/folders/{folder}', [ProjectLibraryController::class, 'folder'])
        ->name('projects.folders.show');

    Route::get('/documents/{document}', [DocumentServeController::class, 'show'])
        ->name('documents.serve');
});

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Contractors
        |--------------------------------------------------------------------------
        */

        Route::get('/contractors', [ContractorController::class, 'index'])
            ->name('contractors.index');

        Route::get('/contractors/create', [ContractorController::class, 'create'])
            ->name('contractors.create');

        Route::post('/contractors', [ContractorController::class, 'store'])
            ->name('contractors.store');

        Route::get('/contractors/{contractor}', [ContractorController::class, 'show'])
            ->name('contractors.show');

        Route::get('/contractors/{contractor}/edit', [ContractorController::class, 'edit'])
            ->name('contractors.edit');

        Route::put('/contractors/{contractor}', [ContractorController::class, 'update'])
            ->name('contractors.update');

        Route::post('/contractors/{contractor}/send-invite', [ContractorController::class, 'sendInvite'])
            ->name('contractors.send-invite');

        /*
        |--------------------------------------------------------------------------
        | Invoices
        |--------------------------------------------------------------------------
        */

        Route::get('/invoices', [AdminInvoiceController::class, 'index'])
            ->name('invoices.index');

        Route::get('/invoices/{invoice}/download', [AdminInvoiceController::class, 'download'])
            ->name('invoices.download');

        Route::post('/invoices/{invoice}/send-for-payment', [AdminInvoiceController::class, 'sendForPayment'])
            ->name('invoices.send-for-payment');

        Route::post('/invoices/{invoice}/return-to-contractor', [AdminInvoiceController::class, 'returnToContractor'])
            ->name('invoices.return-to-contractor');

        Route::get('/invoices/{invoice}', [AdminInvoiceController::class, 'show'])
            ->name('invoices.show');

            Route::post('/users/{user}/send-password-reset', [\App\Http\Controllers\Admin\UserController::class, 'sendPasswordReset'])
    ->name('users.send-password-reset');

    Route::get('/settings/users', [UserController::class, 'index'])
    ->name('settings.users.index');

Route::post('/settings/users', [UserController::class, 'store'])
    ->name('settings.users.store');

Route::post('/settings/users/{user}/send-password-reset', [UserController::class, 'sendPasswordReset'])
    ->name('settings.users.send-password-reset');

        /*
        |--------------------------------------------------------------------------
        | Portal settings
        |--------------------------------------------------------------------------
        */

        Route::get('/settings', [PortalSettingsController::class, 'edit'])
            ->name('settings.edit');

        Route::put('/settings', [PortalSettingsController::class, 'update'])
            ->name('settings.update');

            Route::get('/settings/quote-pack', [QuotePackTemplateController::class, 'edit'])
    ->name('settings.quote-pack.edit');

Route::put('/settings/quote-pack', [QuotePackTemplateController::class, 'update'])
    ->name('settings.quote-pack.update');

    Route::get('/settings/folder-template', [FolderTemplateSettingsController::class, 'edit'])
    ->name('settings.folder-template.edit');

Route::put('/settings/folder-template', [FolderTemplateSettingsController::class, 'update'])
    ->name('settings.folder-template.update');

Route::post('/settings/folder-template/{folderTemplate}/shared-documents', [FolderTemplateSettingsController::class, 'storeSharedDocument'])
    ->name('settings.folder-template.shared-documents.store');

Route::delete('/settings/folder-template/shared-documents/{sharedDocument}', [FolderTemplateSettingsController::class, 'destroySharedDocument'])
    ->name('settings.folder-template.shared-documents.destroy');

    Route::get('/settings/overview', [SettingsHubController::class, 'index'])
    ->name('settings.index');

Route::get('/settings/ai', [AiSettingsController::class, 'edit'])
    ->name('settings.ai.edit');

Route::put('/settings/ai', [AiSettingsController::class, 'update'])
    ->name('settings.ai.update');

        /*
        |--------------------------------------------------------------------------
        | AI & pricing settings
        |--------------------------------------------------------------------------
        */

        Route::get('/pricing-settings', [PricingSettingsController::class, 'edit'])
            ->name('pricing-settings.edit');

        Route::put('/pricing-settings/rate-card', [PricingSettingsController::class, 'updateRateCard'])
            ->name('pricing-settings.rate-card.update');

        Route::post('/pricing-settings/rate-items', [PricingSettingsController::class, 'storeRateItem'])
            ->name('pricing-settings.rate-items.store');

        Route::put('/pricing-settings/rate-items/{rateItem}', [PricingSettingsController::class, 'updateRateItem'])
            ->name('pricing-settings.rate-items.update');

        Route::delete('/pricing-settings/rate-items/{rateItem}', [PricingSettingsController::class, 'destroyRateItem'])
            ->name('pricing-settings.rate-items.destroy');

        Route::post('/pricing-settings/templates', [PricingSettingsController::class, 'storeTemplate'])
            ->name('pricing-settings.templates.store');

        Route::put('/pricing-settings/templates/{template}', [PricingSettingsController::class, 'updateTemplate'])
            ->name('pricing-settings.templates.update');

        Route::delete('/pricing-settings/templates/{template}', [PricingSettingsController::class, 'destroyTemplate'])
            ->name('pricing-settings.templates.destroy');

        /*
        |--------------------------------------------------------------------------
        | Customers
        |--------------------------------------------------------------------------
        */

        Route::get('/customers', [CustomerController::class, 'index'])
            ->name('customers.index');

        Route::get('/customers/create', [CustomerController::class, 'create'])
            ->name('customers.create');

        Route::post('/customers', [CustomerController::class, 'store'])
            ->name('customers.store');

        Route::get('/customers/{customer}', [CustomerController::class, 'show'])
            ->name('customers.show');

        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])
            ->name('customers.edit');

        Route::put('/customers/{customer}', [CustomerController::class, 'update'])
            ->name('customers.update');

        Route::post('/customers/{customer}/invite', [CustomerInviteController::class, 'send'])
            ->name('customers.invite');

        /*
        |--------------------------------------------------------------------------
        | Projects
        |--------------------------------------------------------------------------
        */

        Route::get('/projects', [ProjectController::class, 'index'])
            ->name('projects.index');

        Route::get('/projects/create', [ProjectController::class, 'create'])
            ->name('projects.create');

        Route::post('/projects', [ProjectController::class, 'store'])
            ->name('projects.store');

        Route::get('/projects/{project}', [ProjectController::class, 'show'])
            ->name('projects.show');

        Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])
            ->name('projects.edit');

        Route::put('/projects/{project}', [ProjectController::class, 'update'])
            ->name('projects.update');

        Route::put('/projects/{project}/state', [ProjectController::class, 'updateState'])
            ->name('projects.state.update');

        Route::post('/projects/{project}/contractors', [ProjectController::class, 'assignContractor'])
            ->name('projects.contractors.store');

        Route::delete('/projects/{project}/contractors/{contractor}', [ProjectController::class, 'unassignContractor'])
            ->name('projects.contractors.destroy');

        Route::get('/projects/{project}/audit-log', [ProjectAuditLogController::class, 'index'])
            ->name('projects.audit-log.index');

        // OneDrive-style folder browsing for admins: view one folder level at a
        // time. Read-only navigation; write actions live under the folder/
        // document routes below.
        Route::get('/projects/{project}/browse/{folder}', [ProjectController::class, 'folder'])
            ->name('projects.folders.browse');

        /*
        |--------------------------------------------------------------------------
        | Project folders
        |--------------------------------------------------------------------------
        */

        Route::post('/projects/{project}/folders', [ProjectFolderController::class, 'store'])
            ->middleware('project.writable')
            ->name('projects.folders.store');

        // Define the reorder route before the {folder} routes so the literal
        // "reorder" segment is not captured as a folder id.
        Route::put('/projects/{project}/folders/reorder', [ProjectFolderController::class, 'reorder'])
            ->middleware('project.writable')
            ->name('projects.folders.reorder');

        Route::put('/projects/{project}/folders/{folder}/move', [ProjectFolderController::class, 'move'])
            ->middleware('project.writable')
            ->name('projects.folders.move');

        Route::put('/projects/{project}/folders/{folder}', [ProjectFolderController::class, 'update'])
            ->middleware('project.writable')
            ->name('projects.folders.update');

        Route::delete('/projects/{project}/folders/{folder}', [ProjectFolderController::class, 'destroy'])
            ->middleware('project.writable')
            ->name('projects.folders.destroy');

        Route::put('/projects/{project}/folders/{folder}/permissions', [ProjectFolderController::class, 'permissionsUpdate'])
            ->middleware('project.writable')
            ->name('projects.folders.permissions.update');

        /*
        |--------------------------------------------------------------------------
        | Project documents (upload / delete / copy)
        |--------------------------------------------------------------------------
        | Write-side document operations. Wrapped by `project.writable`
        | (EnsureProjectWritable) so a Complete project rejects all modifying
        | operations; per-operation authorization is enforced by DocumentPolicy
        | (upload/delete/copy => canWrite + project not Complete).
        */

        Route::post('/projects/{project}/folders/{folder}/documents', [ProjectDocumentController::class, 'store'])
            ->middleware('project.writable')
            ->name('projects.documents.store');

        Route::delete('/projects/{project}/documents/{document}', [ProjectDocumentController::class, 'destroy'])
            ->middleware('project.writable')
            ->name('projects.documents.destroy');

        Route::post('/projects/{project}/documents/{document}/copy', [ProjectDocumentController::class, 'copy'])
            ->middleware('project.writable')
            ->name('projects.documents.copy');

        Route::put('/projects/{project}/documents/{document}/move', [ProjectDocumentController::class, 'move'])
            ->middleware('project.writable')
            ->name('projects.documents.move');

        Route::put('/projects/{project}/documents/{document}', [ProjectDocumentController::class, 'update'])
            ->middleware('project.writable')
            ->name('projects.documents.update');

        /*
        |--------------------------------------------------------------------------
        | Quotes
        |--------------------------------------------------------------------------
        */

        Route::get('/quotes', [QuoteController::class, 'index'])
            ->name('quotes.index');

        Route::get('/quotes/create', [QuoteController::class, 'create'])
            ->name('quotes.create');

        Route::post('/quotes', [QuoteController::class, 'store'])
            ->name('quotes.store');

        Route::get('/quotes/{quote}', [QuoteController::class, 'show'])
            ->name('quotes.show');

        Route::get('/quotes/{quote}/edit', [QuoteController::class, 'edit'])
            ->name('quotes.edit');

        Route::put('/quotes/{quote}', [QuoteController::class, 'update'])
            ->name('quotes.update');

        Route::post('/quotes/{quote}/mark-survey-in-progress', [QuoteController::class, 'markSurveyInProgress'])
            ->name('quotes.mark-survey-in-progress');

        Route::post('/quotes/{quote}/mark-survey-completed', [QuoteController::class, 'markSurveyCompleted'])
            ->name('quotes.mark-survey-completed');

        Route::get('/quotes/{quote}/survey', [QuoteController::class, 'survey'])
            ->name('quotes.survey');

        Route::get('/quotes/{quote}/pricing', [QuoteController::class, 'pricing'])
            ->name('quotes.pricing');

        Route::get('/quotes/{quote}/pack', [QuoteController::class, 'pack'])
            ->name('quotes.pack');

        Route::put('/quotes/{quote}/pack', [QuoteController::class, 'updatePack'])
            ->name('quotes.pack.update');

        Route::get('/quotes/{quote}/download', [QuoteController::class, 'download'])
            ->name('quotes.download');

        /*
        |--------------------------------------------------------------------------
        | Quote AI
        |--------------------------------------------------------------------------
        */

        Route::post('/quotes/{quote}/compile-ai', [QuoteAiController::class, 'compile'])
            ->name('quotes.compile-ai');

        Route::post('/quotes/{quote}/ai-draft-items/{item}/accept', [QuoteAiDraftController::class, 'accept'])
            ->name('quotes.ai-draft-items.accept');

        Route::post('/quotes/{quote}/ai-draft-items/{item}/reject', [QuoteAiDraftController::class, 'reject'])
            ->name('quotes.ai-draft-items.reject');

        Route::put('/quotes/{quote}/ai-draft-items/{item}', [QuoteAiDraftController::class, 'update'])
            ->name('quotes.ai-draft-items.update');

        Route::post('/quotes/{quote}/ai-drafts/{draft}/apply-accepted', [QuoteAiDraftController::class, 'applyAccepted'])
            ->name('quotes.ai-drafts.apply-accepted');

        Route::post('/quotes/{quote}/ai-drafts/{draft}/apply-wording', [QuoteAiDraftController::class, 'applyWording'])
            ->name('quotes.ai-drafts.apply-wording');


            // AI NEW
            Route::post('/quotes/{quote}/generate-ai-estimate', [QuoteAiController::class, 'generateEstimate'])
                ->name('quotes.generate-ai-estimate');

            Route::post('/quotes/{quote}/generate-ai-wording', [QuoteAiController::class, 'generateWording'])
                ->name('quotes.generate-ai-wording');

            Route::post('/quotes/{quote}/compile-ai', [QuoteAiController::class, 'compile'])
                ->name('quotes.compile-ai');

                Route::get('/quotes/{quote}/pack', [QuotePackController::class, 'show'])
    ->name('quotes.pack');

Route::put('/quotes/{quote}/pack', [QuotePackController::class, 'update'])
    ->name('quotes.pack.update');

Route::put('/quotes/{quote}/pack/photos', [QuotePackController::class, 'updatePhotos'])
    ->name('quotes.pack.photos.update');

Route::post('/quotes/{quote}/generate-ai-wording', [QuoteAiController::class, 'generateWording'])
    ->name('quotes.generate-ai-wording');

        /*
        |--------------------------------------------------------------------------
        | Quote notes
        |--------------------------------------------------------------------------
        */

        Route::post('/quotes/{quote}/notes', [QuoteNoteController::class, 'store'])
            ->name('quotes.notes.store');

        Route::delete('/quotes/{quote}/notes/{note}', [QuoteNoteController::class, 'destroy'])
            ->name('quotes.notes.destroy');

        /*
        |--------------------------------------------------------------------------
        | Quote line items
        |--------------------------------------------------------------------------
        */

        Route::post('/quotes/{quote}/line-items', [QuoteLineItemController::class, 'store'])
            ->name('quotes.line-items.store');

            Route::put('/quotes/{quote}/line-items/{lineItem}', [QuoteLineItemController::class, 'update'])
    ->name('quotes.line-items.update');


        Route::delete('/quotes/{quote}/line-items/{lineItem}', [QuoteLineItemController::class, 'destroy'])
            ->name('quotes.line-items.destroy');

        /*
        |--------------------------------------------------------------------------
        | Quote files
        |--------------------------------------------------------------------------
        */

        Route::post('/quotes/{quote}/files', [QuoteFileController::class, 'store'])
            ->name('quotes.files.store');

        Route::delete('/quotes/{quote}/files/{file}', [QuoteFileController::class, 'destroy'])
            ->name('quotes.files.destroy');

        /*
        |--------------------------------------------------------------------------
        | Quote follow-ups
        |--------------------------------------------------------------------------
        */

        Route::post('/quotes/{quote}/follow-ups', [QuoteFollowUpController::class, 'store'])
            ->name('quotes.follow-ups.store');

        Route::post('/quotes/{quote}/follow-ups/{followUp}/complete', [QuoteFollowUpController::class, 'complete'])
            ->name('quotes.follow-ups.complete');
    });

require __DIR__ . '/auth.php';