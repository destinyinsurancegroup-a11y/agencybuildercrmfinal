<?php

use Illuminate\Support\Facades\Route;

// ---------------------------------------------
// MODULE 1 — SAFE ROUTING FOUNDATION
// ---------------------------------------------
// These routes DO NOT depend on controllers yet.
// They prevent 403 errors during early deployment.
// ---------------------------------------------

// Home page (temporary placeholder)
Route::get('/', function () {
    return "CRM Home Loaded Successfully (Module 1)";
});

// Dashboard placeholder
Route::get('/dashboard', function () {
    return "Dashboard Placeholder — Module 1";
});

// Book of Business placeholder
Route::get('/book-of-business', function () {
    return "Book of Business Placeholder — Module 1";
});

// My Numbers placeholder
Route::get('/my-numbers', function () {
    return "My Numbers Placeholder — Module 1";
});

// Service page placeholder
Route::get('/service', function () {
    return "Service Placeholder — Module 1";
});

// Settings placeholder
Route::get('/settings', function () {
    return "Settings Placeholder — Module 1";
});
