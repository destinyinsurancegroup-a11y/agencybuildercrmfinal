<?php

use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('/', function () {
    return view('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

// -----------------------------------------------------
// ALL CONTACTS (NO AUTH — YOU CAN SEE IT RIGHT AWAY)
// -----------------------------------------------------
Route::get('/contacts', function () {
    return view('contacts.index');
});
