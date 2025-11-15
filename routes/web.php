<?php

use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return 'ROUTES ARE WORKING';
});

// -----------------------
// existing routes below
// -----------------------

Route::get('/', function () {
    return view('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

Route::get('/contacts', function () {
    return view('contacts.index');
});

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// Dashboard
Route::get('/', function () {
    return view('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

// Contacts
Route::get('/contacts', function () {
    return view('contacts.index');
});

// TEMPORARY CLEAR CACHE ROUTE
Route::get('/clear-cache', function () {
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    return 'Laravel cache cleared!';
});
