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
| - /all-contacts redirects to contacts.index
| - Left panel shows list
| - Right panel loads via AJAX
| - create-panel loads empty form into right panel
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
| LEADS (NEW SECTION)
|--------------------------------------------------------------------------
*/
Route::get('/leads',        [LeadController::class, 'index'])->name('leads.index');
Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
Route::get('/leads/{id}',   [LeadController::class, 'show'])->name('leads.show');

/*
|--------------------------------------------------------------------------
| BOOK OF BUSINESS (NEW SECTION)
|--------------------------------------------------------------------------
*/
Route::get('/book',        [BookController::class, 'index'])->name('book.index');
Route::get('/book/create', [BookController::class, 'create'])->name('book.create');
Route::get('/book/{id}',   [BookController::class, 'show'])->name('book.show');

/*
|--------------------------------------------------------------------------
| SERVICE (NEW SECTION)
|--------------------------------------------------------------------------
*/
Route::get('/service',        [ServiceController::class, 'index'])->name('service.index');
Route::get('/service/create', [ServiceController::class, 'create'])->name('service.create');
Route::get('/service/{id}',   [ServiceController::class, 'show'])->name('service.show');

/*
|--------------------------------------------------------------------------
| NOTES (AJAX)
|--------------------------------------------------------------------------
| These routes power:
| - Notes tab loading
| - Saving a note
| - Reloading updated notes list
|--------------------------------------------------------------------------
*/

// Load Notes tab UI
Route::get('/contacts/{contact}/notes', 
    [NoteController::class, 'index']
)->name('contacts.notes.index');

// Save new note via AJAX
Route::post('/contacts/{contact}/notes', 
    [NoteController::class, 'store']
)->name('contacts.notes.store');

// Reload notes list partial
Route::get('/contacts/{contact}/notes/list', 
    [NoteController::class, 'list']
)->name('contacts.notes.list');

/*
|--------------------------------------------------------------------------
| CALENDAR PAGE (STATIC)
|--------------------------------------------------------------------------
*/
Route::get('/calendar', function () {
    return view('calendar.index');
});

/*
|--------------------------------------------------------------------------
| CALENDAR API ROUTES
|--------------------------------------------------------------------------
*/

/* --------------------------
   FETCH ALL EVENTS
-------------------------- */
Route::get('/calendar/events', function () {
    try {
        return Event::all();
    } catch (\Throwable $e) {
        return response()->json([
            'error'   => true,
            'message' => $e->getMessage()
        ], 500);
    }
});

/* --------------------------
   CREATE EVENT
-------------------------- */
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
        return response()->json([
            'error'   => true,
            'message' => $e->getMessage()
        ], 500);
    }
});

/* --------------------------
   UPDATE EVENT
-------------------------- */
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

        return response()->json([
            'success' => true,
            'event'   => $event
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'error'   => true,
            'message' => $e->getMessage()
        ], 500);
    }
});

/* --------------------------
   DELETE EVENT
-------------------------- */
Route::delete('/calendar/events/{id}', function ($id) {
    try {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully'
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'error'   => true,
            'message' => $e->getMessage()
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| TEMPORARY DB MIGRATION ROUTE
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

/*
|--------------------------------------------------------------------------
| TEMPORARY CLEAR CACHE
|--------------------------------------------------------------------------
*/
Route::get('/clear-cache', function () {
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    return 'Laravel cache cleared!';
});
