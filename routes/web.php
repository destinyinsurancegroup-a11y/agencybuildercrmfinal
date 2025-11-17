<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\CrmEvent;

/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/
Route::get('/', fn() => view('dashboard'));
Route::get('/dashboard', fn() => view('dashboard'));

/*
|--------------------------------------------------------------------------
| CONTACTS
|--------------------------------------------------------------------------
*/
Route::get('/contacts', fn() => view('contacts.index'));

/*
|--------------------------------------------------------------------------
| CALENDAR PAGE
|--------------------------------------------------------------------------
*/
Route::get('/calendar', fn() => view('calendar.index'));

/*
|--------------------------------------------------------------------------
| CALENDAR API (CRUD)
|--------------------------------------------------------------------------
*/

/* --------------------------
   FETCH ALL EVENTS
-------------------------- */
Route::get('/calendar/events', function () {
    try {
        return CrmEvent::all();
    } catch (\Throwable $e) {
        return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
    }
});

/* --------------------------
   CREATE EVENT
-------------------------- */
Route::post('/calendar/events', function (Request $request) {

    try {
        $event = CrmEvent::create([
            'title' => $request->title,
            'start' => $request->start,
            'end'   => $request->end,
            'color' => $request->color,
        ]);

        return response()->json($event, 201);

    } catch (\Throwable $e) {
        return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
    }
});

/* --------------------------
   UPDATE EVENT
-------------------------- */
Route::put('/calendar/events/{id}', function ($id, Request $request) {
    try {
        $event = CrmEvent::findOrFail($id);

        $event->update([
            'title' => $request->title,
            'start' => $request->start,
            'end'   => $request->end,
            'color' => $request->color,
        ]);

        return response()->json(['success' => true, 'event' => $event]);

    } catch (\Throwable $e) {
        return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
    }
});

/* --------------------------
   DELETE EVENT
-------------------------- */
Route::delete('/calendar/events/{id}', function ($id) {
    try {
        CrmEvent::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    } catch (\Throwable $e) {
        return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
    }
});

/*
|--------------------------------------------------------------------------
| DASHBOARD: UPCOMING EVENTS FEED
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/events-upcoming', function () {
    return CrmEvent::orderBy('start', 'asc')
        ->where('start', '>=', now())
        ->limit(5)
        ->get();
});
