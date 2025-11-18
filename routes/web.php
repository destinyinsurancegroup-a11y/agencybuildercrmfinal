<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| TEMPORARY PLACEHOLDER LOGIN ROUTE (Fix for "Route [login] not defined")
|--------------------------------------------------------------------------
*/
Route::get('/login', function () {
    return 'LOGIN PAGE COMING SOON';
})->name('login');

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
| DASHBOARD (Uses DashboardController@index)
|--------------------------------------------------------------------------
|
| This route displays the dashboard and loads events from the controller.
|
*/
Route::get('/', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| CONTACTS
|--------------------------------------------------------------------------
*/
Route::get('/contacts', function () {
    return view('contacts.index');
});

/*
|--------------------------------------------------------------------------
| CALENDAR PAGE
|--------------------------------------------------------------------------
*/
Route::get('/calendar', function () {
    return view('calendar.index');
});

/*
|--------------------------------------------------------------------------
| CALENDAR API ROUTES
|--------------------------------------------------------------------------
| Fetch events + Save events + Update events + Delete events
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
            'error' => true,
            'message' => $e->getMessage()
        ], 500);
    }
});

/* --------------------------
   CREATE EVENT
-------------------------- */
Route::post('/calendar/events', function (Request $request) {
    try {
        $event = Event::create([
            'title'      => $request->title,
            'start'      => $request->start,
            'end'        => $request->end,
            'color'      => $request->color,
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
        $event = Event::findOrFail($id);

        $event->update([
            'title' => $request->title,
            'start' => $request->start,
            'end'   => $request->end,
            'color' => $request->color,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
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
