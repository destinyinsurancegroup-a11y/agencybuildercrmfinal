<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ActivityController extends Controller
{
    // The activity page
    public function index()
    {
        return view('activity.index');
    }

    // The slide-in panel that contains the Current Production card
    public function panel()
    {
        return view('activity.partials.panel');
    }
}
