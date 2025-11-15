<?php

use Illuminate\Support\Facades\Route;

// Root → Dashboard
Route::get('/', function () {
    return view('dashboard');
});

// Also allow /dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
});
