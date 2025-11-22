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
Route::get('/all-contacts', function () {
    return redirect()->route('contacts.index');
});

Route::get('/contacts/create-panel', function () {
    return view('contacts.partials.create');
})->name('contacts.create.panel');

Route::resource('contacts', ContactsController::class);

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
| BOOK OF BUSINESS (AJAX Master-Detail)
|--------------------------------------------------------------------------
*/
Route::prefix('book')->group(function () {

    Route::get('/', [BookController::class, 'index'])->name('book.index');
    Route::get('/create-panel', [BookController::class, 'createPanel'])->name('book.create.panel');
    Route::post('/', [BookController::class, 'store'])->name('book.store');
    Route::get('/{client}', [BookController::class, 'show'])->name('book.show');
    Route::get('/{client}/edit-panel', [BookController::class, 'editPanel'])->name('book.edit.panel');
    Route::put('/{client}', [BookController::class, 'update'])->name('book.update');

    Route::post('/{client}/notes', [BookController::class, 'storeNote'])->name('book.notes.store');
    Route::put('/{client}/notes/{note}', [BookController::class, 'updateNote'])->name('book.notes.update');

    Route::post('/import', [BookController::class, 'import'])->name('book.import');
});

/*
|--------------------------------------------------------------------------
| SERVICE (Clone of BOOK)
|--------------------------------------------------------------------------
*/
Route::prefix('service')->group(function () {

    Route::get('/', [ServiceController::class, 'index'])->name('service.index');
    Route::get('/create-panel', [ServiceController::class, 'createPanel'])->name('service.create.panel');
    Route::post('/', [ServiceController::class, 'store'])->name('service.store');

    Route::get('/{client}', [ServiceController::class, 'show'])->name('service.show');
    Route::get('/{client}/edit-panel', [ServiceController::class, 'editPanel'])->name('service.edit.panel');
    Route::put('/{client}', [ServiceController::class, 'update'])->name('service.update');
});

/*
|--------------------------------------------------------------------------
| SERVICE NOTES (reuse BookController)
|--------------------------------------------------------------------------
*/
Route::post('/service/{client}/notes', [BookController::class, 'storeNote'])->name('service.notes.store');
Route::put('/service/{client}/notes/{note}', [BookController::class, 'updateNote'])->name('service.notes.update');

/*
|--------------------------------------------------------------------------
| SERVICE Beneficiary / Emergency DELETE
|--------------------------------------------------------------------------
*/
Route::delete('/service/{client}/beneficiaries/{beneficiary}',
    [BookController::class, 'deleteBeneficiary'])->name('service.beneficiaries.destroy');

Route::delete('/service/{client}/emergencies/{contact}',
    [BookController::class, 'deleteEmergency'])->name('service.emergencies.destroy');

/*
|--------------------------------------------------------------------------
| ACTIVITY (UPDATED FOR MODAL POPUP)
|--------------------------------------------------------------------------
*/

// full page Activity (kept)
Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');

// popup modal
Route::get('/activity/popup', [ActivityController::class, 'popup'])->name('activity.popup');

// save activity form
Route::post('/activity/store', [ActivityController::class, 'store'])->name('activity.store');

// ⭐ LIVE DASHBOARD TOTALS (NEW)
Route::get('/activity/totals/{range}', [ActivityController::class, 'totals'])
    ->name('activity.totals');

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

        // FIXED — MUST USE ->update() NOT .update
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
| MAINTENANCE UTILITIES
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
