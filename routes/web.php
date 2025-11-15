<?php

use Illuminate\Support\Facades\Route;

// ------------------------------
// DASHBOARD
// ------------------------------
Route::get('/', function () {
    return view('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

// ------------------------------
// ALL CONTACTS (NO AUTH)
// ------------------------------
Route::get('/contacts', function () {
    return view('contacts.index');
});
