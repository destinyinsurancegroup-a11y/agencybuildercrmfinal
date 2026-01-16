<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;

class DripCampaignController extends Controller
{
    public function index()
    {
        return view('settings.messaging.drips.index');
    }
}
