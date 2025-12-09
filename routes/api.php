<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Gideon controller
use App\Http\Controllers\Gideon\GideonChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and assigned to
| the "api" middleware group.
|
*/

/*
|--------------------------------------------------------------------------
| Current logged-in user (requires auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| GIDEON SPARRING PARTNER ENDPOINT
|---------------------------------------------------------------------------
| TEMPORARILY PUBLIC for testing (no auth required yet).
| We will secure this later once UI integration begins.
|--------------------------------------------------------------------------
*/
Route::post('/gideon/ask', [GideonChatController::class, 'ask']);
