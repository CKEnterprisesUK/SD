<?php

use App\Http\Controllers\Admin\ContractorController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\PortalSettingsController;
use App\Http\Controllers\Admin\PricingSettingsController;
use App\Http\Controllers\Admin\QuoteAiController;
use App\Http\Controllers\Admin\QuoteAiDraftController;
use App\Http\Controllers\Admin\QuoteController;
use App\Http\Controllers\Admin\QuoteFileController;
use App\Http\Controllers\Admin\QuoteFollowUpController;
use App\Http\Controllers\Admin\QuoteLineItemController;
use App\Http\Controllers\Admin\QuoteNoteController;
use App\Http\Controllers\Contractor\InvoiceController as ContractorInvoiceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

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

        /*
        |--------------------------------------------------------------------------
        | Portal settings
        |--------------------------------------------------------------------------
        */

        Route::get('/settings', [PortalSettingsController::class, 'edit'])
            ->name('settings.edit');

        Route::put('/settings', [PortalSettingsController::class, 'update'])
            ->name('settings.update');

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