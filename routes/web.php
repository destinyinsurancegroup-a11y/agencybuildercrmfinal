<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes – Tier 1 Navigation
|--------------------------------------------------------------------------
|
| For now, these routes use simple Closures instead of controllers so that
| deployment will NOT fail even if controllers are not created yet.
| We'll swap these to controllers later (Module 2+).
|
*/

/**
 * Default route – send root to dashboard.
 */
Route::get('/', function () {
    return redirect()->route('dashboard');
});

/**
 * Dashboard
 */
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

/**
 * All Contacts
 */
Route::get('/all-contacts', function () {
    return view('all-contacts');
})->name('contacts.index');

/**
 * Book of Business
 */
Route::get('/book-of-business', function () {
    return view('book-of-business');
})->name('book.business');

/**
 * Leads
 */
Route::get('/leads', function () {
    return view('leads');
})->name('leads.index');

/**
 * Service
 */
Route::get('/service', function () {
    return view('service');
})->name('service.index');

/**
 * Activity
 */
Route::get('/activity', function () {
    return view('activity');
})->name('activity.index');

/**
 * Calendar
 */
Route::get('/calendar', function () {
    return view('calendar');
})->name('calendar.index');

/**
 * Settings
 */
Route::get('/settings', function () {
    return view('settings');
})->name('settings.index');

/**
 * Billing
 */
Route::get('/billing', function () {
    return view('billing');
})->name('billing.index');

/**
 * Logout
 *
 * Later this will tie into real auth.
 * For now it just bounces back to the dashboard.
 */
Route::get('/logout', function () {
    // TODO: hook into auth later (Module for authentication)
    return redirect()->route('dashboard');
})->name('logout');
