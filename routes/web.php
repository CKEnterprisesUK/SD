<?php
use App\Http\Controllers\Admin\ContractorController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PortalSettingsController;

Route::get('/', function () {
    return view('welcome');
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
});


require __DIR__.'/auth.php';
