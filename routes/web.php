<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContactsController;

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
| CONTACTS (FULL CRUD)
|--------------------------------------------------------------------------
| This REPLACES the temporary static contacts route you had before.
| It does NOT affect Calendar or Dashboard in any way.
|
| IMPORTANT:
| Remove the old static route:
|     Route::get('/contacts', function () { return view('contacts.index'); });
|
| This static route would have blocked the correct controller.
|--------------------------------------------------------------------------
*/

// REMOVE this old route (commented out so you can see it):
// Route::get('/contacts', function () {
//     return view('contacts.index');
// });

// Correct, full CRUD routes:
Route::resource('contacts', ContactsController::class);

/*
|--------------------------------------------------------------------------
| CALENDAR PAGE (STATIC VIEW)
|--------------------------------------------------------------------------
*/
Route::get('/calendar', function () {
    return view('calendar.index');
});

/*
|--------------------------------------------------------------------------
| CALENDAR API ROUTES (COMPLETE + FIXED)
|--------------------------------------------------------------------------
| These routes handle: fetch, create, update, delete events.
| Fully compatible with your current working FullCalendar v6 setup.
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
   CREATE EVENT (UPDATED)
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
            'end'        => $data['start'], // end not used yet
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
   UPDATE EVENT (UPDATED)
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
