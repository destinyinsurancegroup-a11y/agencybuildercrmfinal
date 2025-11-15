<?php

use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('/', function () {
    return view('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

// Contacts (NO AUTH, SAFE, SIMPLE)
Route::get('/contacts', function () {
    return view('contacts.index');
});
