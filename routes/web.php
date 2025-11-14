<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BookOfBusinessController;
use App\Http\Controllers\MyNumbersController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;

// ---------------------------------------------
// MODULE 2 — ROUTES USING REAL CONTROLLERS
// ---------------------------------------------

Route::get('/', [DashboardController::class, 'index']);
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/book-of-business', [BookOfBusinessController::class, 'index']);
Route::get('/my-numbers', [MyNumbersController::class, 'index']);
Route::get('/service', [ServiceController::class, 'index']);
Route::get('/settings', [SettingsController::class, 'index']);
