<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Gideon controllers / services
use App\Http\Controllers\Gideon\GideonChatController;
use App\Http\Controllers\Gideon\GideonOpportunitiesController;
use App\Http\Controllers\Gideon\GideonSparringController;
use App\Services\Gideon\SparringService;

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
    */
    Route::post('/gideon/ask', [GideonChatController::class, 'ask']);

    /*
    |--------------------------------------------------------------------------
    | SIMPLE BROWSER TEST ENDPOINT (GET)
    |--------------------------------------------------------------------------
    | Lets you test Gideon sparring while logged in.
    | Visit: /api/gideon/test  (while authenticated in the CRM)
    |--------------------------------------------------------------------------
    */
    Route::get('/gideon/test', function (Request $request, SparringService $sparring) {
        $user = $request->user();

        // hard-code one scenario for now
        $sessionData = $sparring->startSession(
            $user->agency_id,
            $user->id,
            'scenario_think_it_over',
            'prospect_simulation'
        );

        $result = $sparring->handleAgentMessage(
            $sessionData['session']->id,
            $user->agency_id,
            $user->id,
            'Quick API test – when you say you need to think it over, what part feels unclear?'
        );

        return response()->json([
            'session'  => $sessionData['session'],
            'state'    => $sessionData['session']->state,
            'opening'  => $sessionData['first_message']?->content,
            'agent'    => $result['agent_message']->content,
            'gideon'   => $result['gideon_reply']->content,
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | GIDEON OPPORTUNITIES LIST
    |--------------------------------------------------------------------------
    */
    Route::get('/gideon/opportunities', [GideonOpportunitiesController::class, 'index']);
});
