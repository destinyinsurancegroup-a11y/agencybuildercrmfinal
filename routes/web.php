<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

use App\Models\Event;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\NoteController;

// NEW CONTROLLERS FOR LEADS / BOOK / SERVICE
use App\Http\Controllers\LeadController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ServiceController;

/*
|--------------------------------------------------------------------------
| TEST ROUTE
|--------------------------------------------------------------------------
*/
Route::get('/test', function () {
    return 'ROUTES ARE WORKING';
});

/*
|--------------------------------------------------------------------------
| DASHBOARD (NO AUTH REQUIRED)
|--------------------------------------------------------------------------
*/
Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| CONTACTS (FULL CRUD + MASTER-DETAIL LAYOUT)
|--------------------------------------------------------------------------
*/

// Friendly alias for menu
Route::get('/all-contacts', function () {
    return redirect()->route('contacts.index');
});

// AJAX route for CREATE PANEL
Route::get('/contacts/create-panel', function () {
    return view('contacts.partials.create');
})->name('contacts.create.panel');

// Contacts CRUD
Route::resource('contacts', ContactsController::class);

// Contacts Import (CSV/Excel)
Route::post('/contacts/import', [ContactsController::class, 'import'])
    ->name('contacts.import');

/*
|--------------------------------------------------------------------------
| LEADS
|--------------------------------------------------------------------------
*/
Route::get('/leads',        [LeadController::class, 'index'])->name('leads.index');
Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
Route::get('/leads/{id}',   [LeadController::class, 'show'])->name('leads.show');

/*
|--------------------------------------------------------------------------
| BOOK OF BUSINESS (UPDATED – COMPLETE)
|--------------------------------------------------------------------------
|
| MATCHES:
| - AJAX two-panel layout (same as contacts + leads)
| - create, show, edit-panel, update
| - notes AJAX
| - import
|--------------------------------------------------------------------------
*/
Route::prefix('book')->group(function () {

    // INDEX PAGE
    Route::get('/', [BookController::class, 'index'])->name('book.index');

    // AJAX: LOAD CREATE FORM
    Route::get('/create-panel', [BookController::class, 'createPanel'])
        ->name('book.create.panel');

    // STORE NEW CLIENT (POST)
    Route::post('/', [BookController::class, 'store'])->name('book.store');

    // AJAX: LOAD CLIENT FILE
    Route::get('/{client}', [BookController::class, 'show'])
        ->name('book.show');

    // ------------------------------------------------------
    // UPDATE 1: CORRECT EDIT PANEL ROUTE
    // ------------------------------------------------------
    Route::get('/{client}/edit', [BookController::class, 'edit'])
        ->name('book.edit');

    // UPDATE CLIENT
    Route::put('/{client}', [BookController::class, 'update'])
        ->name('book.update');

    // NOTES — ADD
    Route::post('/{client}/notes', [BookController::class, 'storeNote'])
        ->name('book.notes.store');

    // NOTES — EDIT
    Route::put('/{client}/notes/{note}', [BookController::class, 'updateNote'])
        ->name('book.notes.update');

    // IMPORT
    Route::post('/import', [BookController::class, 'import'])
        ->name('book.import');
});

/*
|--------------------------------------------------------------------------
| SERVICE
|--------------------------------------------------------------------------
*/
Route::get('/service',        [ServiceController::class, 'index'])->name('service.index');
Route::get('/service/create', [ServiceController::class, 'create'])->name('service.create');
Route::get('/service/{id}',   [ServiceController::class, 'show'])->name('service.show');

/*
|--------------------------------------------------------------------------
| NOTES FOR CONTACTS
|--------------------------------------------------------------------------
*/
Route::get('/contacts/{contact}/notes', [NoteController::class, 'index'])
    ->name('contacts.notes.index');

Route::post('/contacts/{contact}/notes', [NoteController::class, 'store'])
    ->name('contacts.notes.store');

Route::get('/contacts/{contact}/notes/list', [NoteController::class, 'list'])
    ->name('contacts.notes.list');

/*
|--------------------------------------------------------------------------
| CALENDAR
|--------------------------------------------------------------------------
*/
Route::get('/calendar', function () {
    return view('calendar.index');
});

/*
|--------------------------------------------------------------------------
| CALENDAR API
|--------------------------------------------------------------------------
*/
Route::get('/calendar/events', function () {
    try { return Event::all(); }
    catch (\Throwable $e) { return response()->json(['error'=>true,'message'=>$e->getMessage()], 500); }
});

Route::post('/calendar/events', function (Request $request) {
    try {
        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'start'    => 'required|string',
            'location' => 'nullable|string|max:255',
        ]);

        $event = Event::create([
            'title'      => $data['title'],
            'start'      => $data['start'],
            'end'        => $data['start'],
            'location'   => $data['location'] ?? null,
            'tenant_id'  => 1,
            'created_by' => 1,
        ]);

        return response()->json($event, 201);

    } catch (\Throwable $e) {
        return response()->json(['error'=>true,'message'=>$e->getMessage()], 500);
    }
});

Route::put('/calendar/events/{id}', function (Request $request, $id) {
    try {
        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'start'    => 'required|string',
            'location' => 'nullable|string|max:255',
        ]);

        $event = Event::findOrFail($id);

        $event->update([
            'title'    => $data['title'],
            'start'    => $data['start'],
            'end'      => $data['start'],
            'location' => $data['location'] ?? null,
        ]);

        return response()->json(['success'=>true,'event'=>$event]);

    } catch (\Throwable $e) {
        return response()->json(['error'=>true,'message'=>$e->getMessage()], 500);
    }
});

Route::delete('/calendar/events/{id}', function ($id) {
    try {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json(['success'=>true, 'message'=>'Event deleted successfully']);

    } catch (\Throwable $e) {
        return response()->json(['error'=>true,'message'=>$e->getMessage()], 500);
    }
});

/*
|--------------------------------------------------------------------------
| MIGRATION & CACHE UTILS
|--------------------------------------------------------------------------
*/
Route::get('/migrate', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        return 'Migrations ran successfully!';
    } catch (\Throwable $e) {
        return 'Migration error: ' . $e->getMessage();
    }
});

Route::get('/clear-cache', function () {
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    return 'Laravel cache cleared!';
});
