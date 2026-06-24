<?php
use App\Http\Controllers\Admin\ContractorController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PortalSettingsController;
use App\Http\Controllers\Contractor\InvoiceController as ContractorInvoiceController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;

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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/contractors', [ContractorController::class, 'index'])
        ->name('contractors.index');

    Route::get('/contractors/create', [ContractorController::class, 'create'])
        ->name('contractors.create');

    Route::post('/contractors', [ContractorController::class, 'store'])
        ->name('contractors.store');

    Route::get('/settings', [PortalSettingsController::class, 'edit'])
        ->name('settings.edit');

    Route::put('/settings', [PortalSettingsController::class, 'update'])
        ->name('settings.update');

        Route::get('/invoices', [AdminInvoiceController::class, 'index'])
    ->name('invoices.index');

Route::post('/invoices/{invoice}/send-for-payment', [AdminInvoiceController::class, 'sendForPayment'])
    ->name('invoices.send-for-payment');

Route::post('/invoices/{invoice}/return-to-contractor', [AdminInvoiceController::class, 'returnToContractor'])
    ->name('invoices.return-to-contractor');

Route::get('/invoices/{invoice}', [AdminInvoiceController::class, 'show'])
    ->name('invoices.show');

Route::get('/invoices/{invoice}/download', [AdminInvoiceController::class, 'download'])
    ->name('invoices.download');
    
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

});

Route::middleware(['auth'])->prefix('my-invoices')->name('contractor.invoices.')->group(function () {
    Route::get('/', [ContractorInvoiceController::class, 'index'])
        ->name('index');

    Route::get('/create', [ContractorInvoiceController::class, 'create'])
        ->name('create');

    Route::post('/', [ContractorInvoiceController::class, 'store'])
        ->name('store');

    Route::get('/{invoice}/download', [ContractorInvoiceController::class, 'download'])
        ->name('download');

    Route::get('/{invoice}', [ContractorInvoiceController::class, 'show'])
        ->name('show');
});

require __DIR__.'/auth.php';
