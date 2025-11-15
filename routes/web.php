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

// Return all events for the calendar
Route::get('/calendar/events', function () {
    return Event::all();
});

// Add new event created in the calendar
Route::post('/calendar/events', function (Request $request) {
    return Event::create([
        'title' => $request->title,
        'start' => $request->start,
        'end'   => $request->end,
        'color' => $request->color,
        // tenant and user will be handled later
        'tenant_id' => 1,
        'created_by' => null,
    ]);
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
