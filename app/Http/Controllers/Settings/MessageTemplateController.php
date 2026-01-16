<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;

class MessageTemplateController extends Controller
{
    public function index()
    {
        return view('settings.messaging.templates.index');
    }
}
