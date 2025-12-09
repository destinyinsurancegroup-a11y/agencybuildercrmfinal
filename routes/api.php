<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Gideon controllers / services
use App\Http\Controllers\Gideon\GideonChatController;
use App\Http\Controllers\Gideon\GideonOpportunitiesController;
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
| Main endpoint the frontend will call with JSON.
|--------------------------------------------------------------------------
*/
Route::post('/gideon/ask', [GideonChatController::class, 'ask']);

/*
|--------------------------------------------------------------------------
| SIMPLE BROWSER TEST ENDPOINT (GET)
|--------------------------------------------------------------------------
| Lets you test Gideon sparring in a normal browser without Postman.
| Visit: /api/gideon/test
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

/*
|--------------------------------------------------------------------------
| GIDEON OPPORTUNITIES LIST (AUTH REQUIRED)
|--------------------------------------------------------------------------
| Lists opportunities for the authenticated user's agency.
| Example:
|   GET /api/gideon/opportunities
|   GET /api/gideon/opportunities?status=open&category=revive_lead
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->get('/gideon/opportunities', [GideonOpportunitiesController::class, 'index']);
