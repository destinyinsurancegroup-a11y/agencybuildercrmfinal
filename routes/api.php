<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Gideon controllers / services
use App\Http\Controllers\Gideon\GideonChatController;
use App\Http\Controllers\Gideon\GideonOpportunitiesController;
use App\Http\Controllers\Gideon\GideonSparringController;
use App\Http\Controllers\Gideon\SparringSessionController;
use App\Services\Gideon\GideonSparringPartner;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and assigned to
| the "api" middleware group.
|
| We additionally apply the "web" + "auth" middlewares so that
| the logged-in CRM user (session-based) is available to API
| endpoints via Auth::user().
|
*/

Route::middleware(['web', 'auth'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Current logged-in user (requires auth)
    |--------------------------------------------------------------------------
    */
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /*
    |--------------------------------------------------------------------------
    | NEW: GIDEON SPARRING SESSION ENDPOINTS (v1 Brain Engine)
    |--------------------------------------------------------------------------
    | These use the new SparringService and the gideon_* brain tables.
    | - POST   /api/gideon/sparring/sessions           : start a sparring session
    | - POST   /api/gideon/sparring/sessions/{id}/message : send agent message, get Gideon reply
    | - POST   /api/gideon/sparring/sessions/{id}/end  : end session (and later: create assessment)
    | - GET    /api/gideon/sparring/sessions/{id}      : fetch session + transcript
    |--------------------------------------------------------------------------
    */
    Route::prefix('/gideon/sparring')->group(function () {
        Route::post('/sessions', [SparringSessionController::class, 'store']);

        Route::post('/sessions/{session}/message', [SparringSessionController::class, 'sendMessage'])
            ->whereNumber('session');

        Route::post('/sessions/{session}/end', [SparringSessionController::class, 'end'])
            ->whereNumber('session');

        Route::get('/sessions/{session}', [SparringSessionController::class, 'show'])
            ->whereNumber('session');
    });

    /*
    |--------------------------------------------------------------------------
    | LEGACY GIDEON SPARRING PARTNER ENDPOINTS (existing implementation)
    |--------------------------------------------------------------------------
    | - POST /api/gideon/sparring/ask : send a message, get Gideon's reply
    | - POST /api/gideon/sparring/end : end session + get assessment
    | Keep these for backward compatibility while we move to the new engine.
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
