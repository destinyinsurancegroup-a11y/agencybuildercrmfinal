<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Models\Event;

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
Route::get('/', function () {
    return view('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

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
| Load events + Save events
|--------------------------------------------------------------------------
*/

// FETCH EVENTS
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

// CREATE EVENT
Route::post('/calendar/events', function (Request $request) {
    try {
        $event = Event::create([
            'title'      => $request->input('title'),
            'start'      => $request->input('start'),
            'end'        => $request->input('end'),
            'color'      => $request->input('color'),
            'tenant_id'  => 1,
            'created_by' => null,
        ]);

        return response()->json($event, 201);

    } catch (\Throwable $e) {
        return response()->json([
            'error'   => true,
            'message' => $e->getMessage()
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| TEMPORARY CLEAR CACHE (DEV ONLY)
|--------------------------------------------------------------------------
*/
Route::get('/clear-cache', function () {
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    return 'Laravel cache cleared!';
});
