<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingController extends Controller
{
    /**
     * Billing landing page.
     *
     * For now, we show a placeholder until Stripe billing is implemented.
     * When ready, flip FEATURE_BILLING=true and replace the view with the real page.
     */
    public function index(Request $request)
    {
        // Feature flag (recommended). If you don't have config/features.php yet,
        // this will default to false.
        $enabled = (bool) config('features.billing', false);

        if (! $enabled) {
            return view('billing.placeholder');
        }

        // Future: real billing management page (Stripe + customer portal).
        // For now, fall back to placeholder to avoid 500s.
        return view('billing.placeholder');
    }
}
