<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes (Laravel Breeze / Fortify / Jetstream)
|--------------------------------------------------------------------------
|
| This loads all login, logout, password reset, and register routes.
| It also defines the route('login') that Laravel needs.
|
*/

require __DIR__ . '/auth.php';



/*
|--------------------------------------------------------------------------
| Web Routes — Agency Builder CRM
|--------------------------------------------------------------------------
|
| All CRM pages require authentication. Public routes such as login
| and password reset are handled in auth.php (included above).
|
*/

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/dashboard', function () {
        return view('dashboard');
    });

    // -----------------------------------------------------
    // 🟨 All Contacts (MAIN CONTACTS PAGE)
    // -----------------------------------------------------
    Route::get('/contacts', function () {
        return view('contacts.index');
    })->name('contacts.index');

    // -------------------------------------------------------------------
    // Additional placeholder modules (uncomment as you build them)
    // -------------------------------------------------------------------
    // Route::get('/book-of-business', ...);
    // Route::get('/service-department', ...);
    // Route::get('/agents', ...);
});
