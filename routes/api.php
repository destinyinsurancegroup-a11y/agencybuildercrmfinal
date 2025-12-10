<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Gideon controllers / services
use App\Http\Controllers\Gideon\GideonChatController;
use App\Http\Controllers\Gideon\GideonOpportunitiesController;
use App\Http\Controllers\Gideon\GideonSparringController;
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
| GIDEON ROUTES (AUTH REQUIRED)
|--------------------------------------------------------------------------
| Sparring Partner, Second Brain chat, and Opportunities.
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | GIDEON SPARRING PARTNER ENDPOINTS
    |--------------------------------------------------------------------------
    | - POST /api/gideon/sparring/ask : send a message, get Gideon's reply
    | - POST /api/gideon/sparring/end : end session + get assessment
    |--------------------------------------------------------------------------
    */
    Route::post('/gideon/sparring/ask', [GideonSparringController::class, 'ask']);
    Route::post('/gideon/sparring/end', [GideonSparringController::class, 'end']);

    /*
    |--------------------------------------------------------------------------
    | GIDEON GENERAL CHAT (SECOND BRAIN, ETC.)
    |--------------------------------------------------------------------------
    | Existing chat endpoint for non-sparring Gideon conversations.
    |--------------------------------------------------------------------------
    */
    Route::post('/gideon/ask', [GideonChatController::class, 'ask']);

    /*
    |--------------------------------------------------------------------------
    | SIMPLE BROWSER TEST ENDPOINT (GET)
    |--------------------------------------------------------------------------
    | Lets you test Gideon sparring while logged in.
    | Visit: /api/gideon/test
    |--------------------------------------------------------------------------
    */
    Route::get('/gideon/test', function (Request $request, GideonSparringPartner $sparring) {
        $user = $request->user();

        // Start a lightweight test session and send a single test message
        $session = $sparring->startSession($user, 'standard', null, ['source' => 'api_test']);
        $reply   = $sparring->handleAgentMessage($session, 'Test the Gideon sparring API wiring.');

        return response()->json([
            'session_id' => $session->id,
            'reply'      => $reply,
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | GIDEON OPPORTUNITIES LIST
    |--------------------------------------------------------------------------
    | Lists opportunities for the authenticated user's agency.
    | Example:
    |   GET /api/gideon/opportunities
    |   GET /api/gideon/opportunities?status=open&category=revive_lead
    |--------------------------------------------------------------------------
    */
    Route::get('/gideon/opportunities', [GideonOpportunitiesController::class, 'index']);
});
