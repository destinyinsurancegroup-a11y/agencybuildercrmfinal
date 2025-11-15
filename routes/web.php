<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// TEST ROUTE
Route::get('/test', function () {
    return 'ROUTES ARE WORKING';
});

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

// Calendar
Route::get('/calendar', function () {
    return view('calendar.index');
});

// TEMPORARY CLEAR CACHE
Route::get('/clear-cache', function () {
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    return 'Laravel cache cleared!';
});
