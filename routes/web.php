<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Contacts\ContactController;

// -------------------------------------------------------------
// Public Landing (Optional)
// -------------------------------------------------------------
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// -------------------------------------------------------------
// Authenticated Routes (Required for Multi-Tenant Security)
// -------------------------------------------------------------
Route::middleware(['auth'])->group(function () {

    // ---------------------------------------------------------
    // Dashboard
    // ---------------------------------------------------------
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // ---------------------------------------------------------
    // All Contacts (Unified Contacts Table)
    // ---------------------------------------------------------
    Route::prefix('contacts')->group(function () {

        // All Contacts list
        Route::get('/', [ContactController::class, 'index'])
            ->name('contacts.index');

        // View a specific contact
        Route::get('/{contact}', [ContactController::class, 'show'])
            ->name('contacts.show');

        // Edit page
        Route::get('/{contact}/edit', [ContactController::class, 'edit'])
            ->name('contacts.edit');

        // Update a contact
        Route::put('/{contact}', [ContactController::class, 'update'])
            ->name('contacts.update');

        // Create
        Route::get('/create/new', [ContactController::class, 'create'])
            ->name('contacts.create');

        // Store new contact
        Route::post('/', [ContactController::class, 'store'])
            ->name('contacts.store');

        // Delete contact
        Route::delete('/{contact}', [ContactController::class, 'destroy'])
            ->name('contacts.destroy');
    });
});
