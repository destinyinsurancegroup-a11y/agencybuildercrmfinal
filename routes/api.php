<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Gideon controllers / services
use App\Http\Controllers\Gideon\GideonChatController;
use App\Http\Controllers\Gideon\GideonOpportunitiesController;
use App\Http\Controllers\Gideon\GideonSparringController;
use App\Services\Gideon\SparringService;

Route::middleware(['web', 'auth'])->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /*
    |--------------------------------------------------------------------------
    | GIDEON SPARRING PARTNER ENDPOINTS
    |--------------------------------------------------------------------------
    */
    Route::post('/gideon/sparring/start', [GideonSparringController::class, 'start']); // ✅ ADD
    Route::post('/gideon/sparring/ask',   [GideonSparringController::class, 'ask']);
    Route::post('/gideon/sparring/end',   [GideonSparringController::class, 'end']);

    /*
    |--------------------------------------------------------------------------
    | GIDEON GENERAL CHAT
    |--------------------------------------------------------------------------
    */
    Route::post('/gideon/ask', [GideonChatController::class, 'ask']);

    /*
    |--------------------------------------------------------------------------
    | SIMPLE BROWSER TEST ENDPOINT (GET)
    |--------------------------------------------------------------------------
    */
    Route::get('/gideon/test', function (Request $request, SparringService $sparring) {
        $user = $request->user();

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
