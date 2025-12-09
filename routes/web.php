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
// NEW ACTIVITY CONTROLLER
use App\Http\Controllers\ActivityController;
// AUTH CONTROLLER
use App\Http\Controllers\Auth\AuthenticatedSessionController;
// GIDEON LLM CLIENT
use App\Services\Gideon\GideonLlmClient;

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

// Show login form
Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

// Handle login POST
Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->name('login.store');

// Handle logout POST
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| DEBUG / TEST (OPTIONAL – not behind auth)
|--------------------------------------------------------------------------
*/

Route::get('/debug-laravel-log', function () {
    $path = storage_path('logs/laravel.log');
    if (! file_exists($path)) {
        return "No laravel.log file found.";
    }
    return nl2br(e(file_get_contents($path)));
});

Route::get('/test', fn () => 'ROUTES ARE WORKING');

/**
 * TEMPORARY: Gideon LLM connectivity test.
 * Hit /gideon-test in the browser to confirm OpenAI is wired up.
 * Remove this route after validation.
 */
Route::get('/gideon-test', function (GideonLlmClient $client) {
    // Optional: you can guard behind config if you want:
    // if (! config('gideon.enabled')) abort(404);

    return $client->testPing();
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED APPLICATION ROUTES
|--------------------------------------------------------------------------
|
| All core CRM functionality is behind auth. After login, users hit these.
|
*/
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | CONTACTS (FULL CRUD + AJAX RIGHT PANEL)
    |--------------------------------------------------------------------------
    */
    Route::get('/all-contacts', fn () => redirect()->route('contacts.index'));

    Route::get('/contacts/create-panel', fn () => view('contacts.partials.create'))
        ->name('contacts.create.panel');

    Route::resource('contacts', ContactsController::class);

    Route::post('/contacts/import', [ContactsController::class, 'import'])
        ->name('contacts.import');

    /*
    |--------------------------------------------------------------------------
    | CONTACT NOTES
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
    | LEADS
    |--------------------------------------------------------------------------
    */
    Route::get('/leads',          [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/archived', [LeadController::class, 'archived'])->name('leads.archived'); // ⭐ Archived leads
    Route::get('/leads/create',   [LeadController::class, 'create'])->name('leads.create');
    Route::get('/leads/{id}',     [LeadController::class, 'show'])->name('leads.show');

    /* ⭐ CONVERT LEAD TO CLIENT ⭐ */
    Route::post('/leads/{contact}/sold', [LeadController::class, 'markSold'])
        ->name('leads.sold');

    /* ⭐ ARCHIVE LEAD AS NOT INTERESTED ⭐ */
    Route::post('/leads/{contact}/archive', [LeadController::class, 'archive'])
        ->name('leads.archive');

    /*
    |--------------------------------------------------------------------------
    | LEAD NOTES  ⭐ (reuse BookController note logic)
    |--------------------------------------------------------------------------
    */
    Route::post('/leads/{client}/notes',       [BookController::class, 'storeNote'])
        ->name('leads.notes.store');

    Route::put('/leads/{client}/notes/{note}', [BookController::class, 'updateNote'])
        ->name('leads.notes.update');

    Route::delete('/leads/{client}/notes/{note}', [BookController::class, 'destroyNote'])
        ->name('leads.notes.destroy');

    /*
    |--------------------------------------------------------------------------
    | BOOK OF BUSINESS (MASTER-DETAIL AJAX)
    |--------------------------------------------------------------------------
    */
    Route::prefix('book')->group(function () {

        Route::get('/', [BookController::class, 'index'])->name('book.index');
        Route::get('/create-panel', [BookController::class, 'createPanel'])->name('book.create.panel');
        Route::post('/', [BookController::class, 'store'])->name('book.store');
        Route::get('/{client}', [BookController::class, 'show'])->name('book.show');
        Route::get('/{client}/edit-panel', [BookController::class, 'editPanel'])->name('book.edit.panel');
        Route::put('/{client}', [BookController::class, 'update'])->name('book.update');

        // NOTES FOR BOOK CLIENTS
        Route::post('/{client}/notes', [BookController::class, 'storeNote'])
            ->name('book.notes.store');

        Route::put('/{client}/notes/{note}', [BookController::class, 'updateNote'])
            ->name('book.notes.update');

        // DELETE NOTE FOR BOOK CLIENT
        Route::delete('/{client}/notes/{note}', [BookController::class, 'destroyNote'])
            ->name('book.notes.destroy');

        Route::post('/import', [BookController::class, 'import'])->name('book.import');

        // Send existing Book client into Service using the same contact record
        Route::post('/{client}/send-to-service', [BookController::class, 'sendToService'])
            ->name('book.send-to-service');
    });

    /*
    |--------------------------------------------------------------------------
    | SERVICE
    |--------------------------------------------------------------------------
    */
    Route::prefix('service')->group(function () {

        // Index + create/store
        Route::get('/', [ServiceController::class, 'index'])->name('service.index');
        Route::get('/create-panel', [ServiceController::class, 'createPanel'])->name('service.create.panel');
        Route::post('/', [ServiceController::class, 'store'])->name('service.store');

        // ARCHIVE VIEWS (must come before /{client} so they aren't swallowed)
        Route::get('/archive', [ServiceController::class, 'archive'])
            ->name('service.archive');

        Route::get('/archive/not-saved', [ServiceController::class, 'notSavedArchive'])
            ->name('service.archive.not-saved');

        // ROUTES OPERATING ON A SPECIFIC CLIENT/SERVICE RECORD
        Route::get('/{client}',            [ServiceController::class, 'show'])->name('service.show');
        Route::get('/{client}/edit-panel', [ServiceController::class, 'editPanel'])->name('service.edit.panel');
        Route::put('/{client}',            [ServiceController::class, 'update'])->name('service.update');

        // Follow Up (opens calendar pre-filled from service record)
        Route::get('/{client}/follow-up', [ServiceController::class, 'followUp'])
            ->name('service.follow-up');

        // Outcomes that KEEP / PUT client on books (and archive service)
        Route::post('/{client}/saved', [ServiceController::class, 'markSaved'])
            ->name('service.saved');

        Route::post('/{client}/back-on-books', [ServiceController::class, 'markBackOnBooks'])
            ->name('service.back-on-books');

        // Outcomes that mark business as NOT SAVED (and may remove from book)
        Route::post('/{client}/not-interested', [ServiceController::class, 'markNotInterested'])
            ->name('service.not-interested');

        Route::post('/{client}/cancelled', [ServiceController::class, 'markCancelled'])
            ->name('service.cancelled');

        // Generic archive action for a single service record
        Route::post('/{client}/archive', [ServiceController::class, 'archiveSingle'])
            ->name('service.archive-single');
    });

    /*
    |--------------------------------------------------------------------------
    | SERVICE NOTES
    |--------------------------------------------------------------------------
    */
    Route::post('/service/{client}/notes',       [BookController::class, 'storeNote'])
        ->name('service.notes.store');

    Route::put('/service/{client}/notes/{note}', [BookController::class, 'updateNote'])
        ->name('service.notes.update');

    // DELETE NOTE FOR SERVICE CLIENT
    Route::delete('/service/{client}/notes/{note}', [BookController::class, 'destroyNote'])
        ->name('service.notes.destroy');

    /*
    |--------------------------------------------------------------------------
    | SERVICE Beneficiary / Emergency DELETE
    |--------------------------------------------------------------------------
    */
    Route::delete(
        '/service/{client}/beneficiaries/{beneficiary}',
        [BookController::class, 'deleteBeneficiary']
    )->name('service.beneficiaries.destroy');

    Route::delete(
        '/service/{client}/emergencies/{contact}',
        [BookController::class, 'deleteEmergency']
    )->name('service.emergencies.destroy');

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY
    |--------------------------------------------------------------------------
    */
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('/activity/popup', [ActivityController::class, 'popup'])->name('activity.popup');
    Route::post('/activity/store', [ActivityController::class, 'store'])->name('activity.store');

    Route::get('/activity/totals/{range}', [ActivityController::class, 'totals'])
        ->name('activity.totals');

    /*
    |--------------------------------------------------------------------------
    | CALENDAR
    |--------------------------------------------------------------------------
    */
    Route::get('/calendar', fn () => view('calendar.index'));

    /*
    |--------------------------------------------------------------------------
    | CALENDAR API
    |--------------------------------------------------------------------------
    | Uses Event model, which is TenantScoped, so it auto-filters by agency_id.
    */
    Route::get('/calendar/events', function (Request $request) {
        return Event::all(); // TenantScoped ensures only current agency's events
    });

    Route::post('/calendar/events', function (Request $request) {

        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'start'      => 'required|string',
            'location'   => 'nullable|string|max:255',
            'contact_id' => 'nullable|integer|exists:contacts,id',
        ]);

        $event = Event::create([
            'title'      => $data['title'],
            'start'      => $data['start'],
            'end'        => $data['start'],
            'location'   => $data['location'] ?? null,
            'contact_id' => $data['contact_id'] ?? null,
            // agency_id is auto-set by TenantScoped
        ]);

        return response()->json($event, 201);
    });

    Route::put('/calendar/events/{id}', function (Request $request, $id) {

        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'start'      => 'required|string',
            'location'   => 'nullable|string|max:255',
            'contact_id' => 'nullable|integer|exists:contacts,id',
        ]);

        $event = Event::findOrFail($id);

        $event->update([
            'title'      => $data['title'],
            'start'      => $data['start'],
            'end'        => $data['start'],
            'location'   => $data['location'] ?? null,
            'contact_id' => $data['contact_id'] ?? $event->contact_id,
        ]);

        return response()->json(['success' => true, 'event' => $event]);
    });

    Route::delete('/calendar/events/{id}', function ($id) {
        Event::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    });

    /*
    |--------------------------------------------------------------------------
    | MAINTENANCE UTILITIES  (still here, but now require auth)
    |--------------------------------------------------------------------------
    */
    Route::get('/migrate', fn () => Artisan::call('migrate', ['--force' => true]) ? 'Migrations ran successfully!' : 'Error');

    Route::get('/clear-cache', fn () => tap('Laravel cache cleared!', function () {
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
    }));
});
