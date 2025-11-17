<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Models\Event;

/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/
Route::view('/', 'dashboard');
Route::view('/dashboard', 'dashboard');

/*
|--------------------------------------------------------------------------
| CONTACTS
|--------------------------------------------------------------------------
*/
Route::view('/contacts', 'contacts.index');

/*
|--------------------------------------------------------------------------
| CALENDAR PAGE
|--------------------------------------------------------------------------
*/
Route::view('/calendar', 'calendar.index');

/*
|--------------------------------------------------------------------------
| CALENDAR API ROUTES (JSON SAFE)
|--------------------------------------------------------------------------
*/

/* === GET ALL EVENTS === */
Route::get('/calendar/events', function () {
    return Event::orderBy('start', 'asc')->get();
});

/* === CREATE EVENT === */
Route::post('/calendar/events', function (Request $request) {
    $event = Event::create([
        'title' => $request->title,
        'start' => $request->start,
        'end'   => $request->end,
        'color' => $request->color,
        'tenant_id'  => 1,
        'created_by' => 1,
    ]);

    return response()->json($event);
});

/* === UPDATE EVENT === */
Route::put('/calendar/events/{id}', function (Request $request, $id) {

    $event = Event::findOrFail($id);

    $event->update([
        'title' => $request->title,
        'start' => $request->start,
        'end'   => $request->end,
        'color' => $request->color,
    ]);

    return response()->json(['success' => true]);
});

/* === DELETE EVENT === */
Route::delete('/calendar/events/{id}', function ($id) {

    $event = Event::findOrFail($id);
    $event->delete();

    return response()->json(['success' => true]);
});

/*
|--------------------------------------------------------------------------
| DATABASE MIGRATION (TEMPORARY)
|--------------------------------------------------------------------------
*/
Route::get('/migrate', function () {
    Artisan::call('migrate', ['--force' => true]);
    return 'Migrations complete.';
});

/*
|--------------------------------------------------------------------------
| CLEAR CACHE
|--------------------------------------------------------------------------
*/
Route::get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    return 'Cache cleared!';
});
