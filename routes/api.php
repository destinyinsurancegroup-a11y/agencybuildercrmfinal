<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Gideon controller
use App\Http\Controllers\Gideon\GideonChatController;
// Gideon Sparring service (for simple test route)
use App\Services\Gideon\GideonSparringPartner;

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
| GIDEON SPARRING PARTNER ENDPOINT (POST)
|--------------------------------------------------------------------------
| This is the main endpoint the frontend will call with JSON.
| Example: POST /api/gideon/ask
|--------------------------------------------------------------------------
*/
Route::post('/gideon/ask', [GideonChatController::class, 'ask']);

/*
|--------------------------------------------------------------------------
| SIMPLE BROWSER TEST ENDPOINT (GET)
|--------------------------------------------------------------------------
| This lets you test Gideon sparring in a normal browser without Postman.
| Just visit: /api/gideon/test in your browser.
|--------------------------------------------------------------------------
*/
Route::get('/gideon/test', function (GideonSparringPartner $sparring) {
    $result = $sparring->ask([
        'prompt'        => 'Give one short sentence confirming the Gideon sparring API is wired up.',
        'scenario_type' => 'generic_coaching',
        'persona'       => null,
    ]);

    return response()->json($result);
});
