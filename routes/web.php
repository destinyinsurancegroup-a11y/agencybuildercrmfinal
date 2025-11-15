<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Agency Builder CRM
|--------------------------------------------------------------------------
|
| All UI routes for authenticated users.
| Public routes (login, forgot password) are provided by Laravel Breeze/Fortify.
|
*/

// 🔐 All CRM pages require authentication
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/dashboard', function () {
        return view('dashboard');
    });

    // -----------------------------------------------------
    // 🟨 All Contacts (THIS FIXES YOUR 404)
    // -----------------------------------------------------
    Route::get('/contacts', function () {
        return view('contacts.index');
    })->name('contacts.index');

    // Future modules (placeholders)
    // Route::get('/book-of-business', ...);
    // Route::get('/service-department', ...);
    // Route::get('/hired-agents', ...);
});

