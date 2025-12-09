<?php

namespace App\Http\Controllers\Gideon;

use App\Http\Controllers\Controller;
use App\Services\Gideon\GideonSparringPartner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GideonChatController extends Controller
{
    protected GideonSparringPartner $sparring;

    public function __construct(GideonSparringPartner $sparring)
    {
        $this->sparring = $sparring;
    }

    /**
     * Handle a sparring / coaching request from the frontend or API client.
     *
     * Expected JSON body:
     * {
     *   "prompt": "string",                     // required
     *   "scenario_type": "single_prospect",    // optional
     *   "persona": "skeptical_fe_client"       // optional
     * }
     */
    public function ask(Request $request): JsonResponse
    {
        $data = $request->validate([
            'prompt'        => 'required|string',
            'scenario_type' => 'nullable|string',
            'persona'       => 'nullable|string',
            'entity_type'   => 'nullable|string',
            'entity_id'     => 'nullable|integer',
        ]);

        $result = $this->sparring->ask($data);

        return response()->json($result);
    }
}
