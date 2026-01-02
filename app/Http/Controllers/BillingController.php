<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        // If billing isn't enabled yet, show the placeholder.
        if (!config('features.billing')) {
            return view('billing.placeholder');
        }

        // Later: when you implement Stripe, you’ll replace this with the real billing page.
        return view('billing.index');
    }
}
