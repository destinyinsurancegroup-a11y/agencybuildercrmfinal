<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Full Activity page view
     */
    public function index()
    {
        return view('activity.index');
    }

    /**
     * AJAX: Right-panel view (will show the Production Card)
     */
    public function panel()
    {
        return view('activity.panel');
    }
}
